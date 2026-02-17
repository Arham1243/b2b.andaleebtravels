<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Config;
use App\Models\Country;
use App\Models\TravelInsurance;
use App\Models\TravelInsurancePassenger;
use App\Services\TravelInsuranceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TravelInsuranceController extends Controller
{
    protected $insuranceService;
    protected $adminEmail;
    protected $insuranceCommissionPercentage;

    public function __construct(TravelInsuranceService $insuranceService)
    {
        $this->insuranceService = $insuranceService;
        $config = Config::pluck('config_value', 'config_key')->toArray();
        $this->adminEmail = $config['ADMINEMAIL'] ?? 'info@andaleebtours.com';
        $this->insuranceCommissionPercentage = $config['INSURANCE_COMMISSION_PERCENTAGE'] ?? 30;
    }

    public function index(Request $request)
    {
        if ($request->has('origin') && $request->has('destination')) {
            try {
                $params = [
                    'origin' => $request->input('origin'),
                    'destination' => $request->input('destination'),
                    'start_date' => $request->input('start_date'),
                    'return_date' => $request->input('return_date'),
                    'residence_country' => $request->input('residence_country'),
                    'adult_count' => $request->input('adult_count', 0),
                    'children_count' => $request->input('children_count', 0),
                    'infant_count' => $request->input('infant_count', 0),
                    'adult_ages' => $request->input('adult_ages', []),
                    'children_ages' => $request->input('children_ages', []),
                ];

                $data = $this->insuranceService->getAvailablePlans($params);

                return view('frontend.travel-insurance.index', compact('data'));
            } catch (\Exception $e) {
                return back()->with('notify_error', 'Failed to fetch insurance plans: ' . $e->getMessage());
            }
        }

        return view('frontend.travel-insurance.index');
    }

    public function details(Request $request)
    {
        $countries = Country::orderBy('name', 'asc')->get();
        $selectedPlanData = null;

        if ($request->has('plan') && $request->has('origin') && $request->has('destination')) {
            try {
                $params = [
                    'origin' => $request->input('origin'),
                    'destination' => $request->input('destination'),
                    'start_date' => $request->input('start_date'),
                    'return_date' => $request->input('return_date'),
                    'residence_country' => $request->input('residence_country'),
                    'adult_count' => $request->input('adult_count', 0),
                    'children_count' => $request->input('children_count', 0),
                    'infant_count' => $request->input('infant_count', 0),
                    'adult_ages' => $request->input('adult_ages', []),
                    'children_ages' => $request->input('children_ages', []),
                ];

                $data = $this->insuranceService->getAvailablePlans($params);
                $selectedPlan = $request->input('plan');
                [$planCode, $ssrFeeCode] = explode('~', $selectedPlan);

                // Find the selected plan from available plans or upsell plans
                $allPlans = array_merge(
                    $data['available_plans'] ?? [],
                    array_map(function ($group) {
                        return $group['UpsellPlans']['UpsellPlan'] ?? [];
                    }, $data['available_upsell_plans'] ?? [])
                );

                foreach ($allPlans as $plan) {
                    if (
                        isset($plan['PlanCode']) && $plan['PlanCode'] === $planCode &&
                        isset($plan['SSRFeeCode']) && $plan['SSRFeeCode'] === $ssrFeeCode
                    ) {
                        $selectedPlanData = $plan;
                        break;
                    }
                }
            } catch (\Exception $e) {
                // If API fails, continue without plan data
            }
        }

        return view('frontend.travel-insurance.details', compact('countries', 'selectedPlanData'));
    }

    public function processPayment(Request $request)
    {
        try {
            $request->validate([
                'plan_code' => 'required|string',
                'ssr_fee_code' => 'required|string',
                'origin' => 'required|string',
                'destination' => 'required|string',
                'start_date' => 'required|date',
                'return_date' => 'required|date',
                'adult_count' => 'required|integer|min:0',
                'children_count' => 'required|integer|min:0',
                'infant_count' => 'required|integer|min:0',
                'residence_country' => 'required|string',
                'payment_method' => 'required|in:payby,tabby',
                'lead.fname' => 'required|string',
                'lead.email' => 'required|email',
                'lead.number' => 'required|string',
                'lead.country_of_residence' => 'required|string',
            ]);

            $adultCount = $request->input('adult_count', 0);
            $childCount = $request->input('children_count', 0);
            $infantCount = $request->input('infant_count', 0);

            if ($adultCount > 0 && $request->has('adult')) {
                foreach ($request->input('adult.dob', []) as $index => $dob) {
                    $age = \Carbon\Carbon::parse($dob)->age;
                    if ($age < 18) {
                        return back()->with('notify_error', 'Adult passenger #' . ($index + 1) . ' must be 18 years or older.');
                    }
                }
            }

            if ($childCount > 0 && $request->has('child')) {
                foreach ($request->input('child.dob', []) as $index => $dob) {
                    $age = \Carbon\Carbon::parse($dob)->age;
                    if ($age < 2 || $age >= 18) {
                        return back()->with('notify_error', 'Child passenger #' . ($index + 1) . ' must be between 2 and 17 years old.');
                    }
                }
            }

            if ($infantCount > 0 && $request->has('infant')) {
                foreach ($request->input('infant.dob', []) as $index => $dob) {
                    $age = \Carbon\Carbon::parse($dob)->age;
                    if ($age >= 2) {
                        return back()->with('notify_error', 'Infant passenger #' . ($index + 1) . ' must be under 2 years old.');
                    }
                }
            }

            $data = $request->all();
            $data['total_premium'] = $request->input('total_premium', 0);

            $insurance = $this->insuranceService->createInsuranceRecord($data);

            $redirectUrl = $this->insuranceService->getRedirectUrl($insurance, $data['payment_method']);

            return redirect($redirectUrl);
        } catch (\Exception $e) {
            Log::error('Travel Insurance Payment Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('notify_error', 'Failed to process payment: ' . $e->getMessage());
        }
    }

    public function paymentSuccess(Request $request, $insurance)
    {
        try {
            $insurance = TravelInsurance::findOrFail($insurance);

            if ($insurance->payment_status === 'paid') {
                return redirect()->route('frontend.index');
            }

            if ($insurance->payment_method === 'payby') {
                $verification = $this->insuranceService->verifyPayByPayment($insurance);
            } else {
                $verification = $this->insuranceService->verifyTabbyPayment($insurance);
            }

            if ($verification['success']) {
                $paymentData = $verification['data'];

                // Save PayBy specific data
                if ($insurance->payment_method === 'payby') {
                    $insurance->update([
                        'payby_merchant_order_no' => $paymentData['body']['acquireOrder']['merchantOrderNo'] ?? null,
                        'payby_order_no' => $paymentData['body']['acquireOrder']['orderNo'] ?? null,
                        'payby_payment_response' => json_encode($paymentData),
                    ]);
                }

                // Update payment status
                $insurance->update([
                    'payment_status' => 'paid',
                    'payment_response' => json_encode($paymentData)
                ]);

                // Call confirm purchase API to get policy details
                $confirmResult = $this->insuranceService->confirmPurchase($insurance);

                if ($confirmResult['success']) {
                    $confirmData = $confirmResult['data'];
                    $purchaseResponse = $confirmData['PurchaseResponse'] ?? $confirmData['ConfirmPurchaseResponse'] ?? [];

                    $proposalState = $purchaseResponse['ProposalState'] ?? null;
                    $isConfirmed = $proposalState === 'CONFIRMED';
                    $errorCode = $purchaseResponse['ErrorCode'] ?? null;

                    // Update insurance with confirmation data
                    $insurance->update([
                        'proposal_state' => $proposalState,
                        'policy_numbers' => $purchaseResponse['PolicyNo'] ?? null,
                        'confirmed_passengers' => json_encode($purchaseResponse['ConfirmedPassengers'] ?? []),
                        'error_messages' => json_encode($purchaseResponse['ErrorMessage'] ?? []),
                        'booking_confirmed' => $isConfirmed,
                        'confirmation_response' => json_encode($confirmData),
                        'status' => $isConfirmed ? 'confirmed' : 'failed',
                    ]);

                    // Update passenger policy details
                    if ($isConfirmed && isset($purchaseResponse['ConfirmedPassengers']['ConfirmedPassenger'])) {
                        $confirmedPassengers = $purchaseResponse['ConfirmedPassengers']['ConfirmedPassenger'];

                        // Handle single passenger (not array of arrays)
                        if (isset($confirmedPassengers['FirstName'])) {
                            $confirmedPassengers = [$confirmedPassengers];
                        }

                        foreach ($confirmedPassengers as $confirmedPassenger) {
                            $passenger = TravelInsurancePassenger::where('travel_insurance_id', $insurance->id)
                                ->where('passport_number', $confirmedPassenger['IdentityNo'] ?? '')
                                ->where('date_of_birth', $confirmedPassenger['DOB'] ?? '')
                                ->where('last_name', $confirmedPassenger['LastName'] ?? '')
                                ->where('first_name', $confirmedPassenger['FirstName'] ?? '')
                                ->first();

                            if ($passenger) {
                                $passenger->update([
                                    'policy_number' => $confirmedPassenger['PolicyNo'] ?? null,
                                    'policy_url_link' => $confirmedPassenger['PolicyURLLink'] ?? null,
                                    'insurance_details' => json_encode($confirmedPassenger),
                                ]);
                            }
                        }
                    }

                    Log::info('Travel Insurance Confirmed', [
                        'insurance_id' => $insurance->id,
                        'proposal_state' => $proposalState,
                        'booking_confirmed' => $isConfirmed
                    ]);

                    // If not confirmed, show failure page
                    if (!$isConfirmed) {
                        $errorMessage = is_array($purchaseResponse['ErrorMessage'] ?? null)
                            ? implode(', ', $purchaseResponse['ErrorMessage'])
                            : ($purchaseResponse['ErrorMessage'] ?? 'Insurance confirmation failed');

                        $insurance->update([
                            'payment_status' => 'paid',
                            'status' => 'failed'
                        ]);

                        $this->sendFailureEmails($insurance);

                        return view('frontend.travel-insurance.payment-failed', compact('insurance'))
                            ->with('notify_error', 'Your payment was successful, but insurance confirmation failed: ' . $errorMessage)
                            ->with('notify_error', 'Your payment was successful, but insurance confirmation failed: ' . $errorMessage);
                    }
                } else {
                    Log::warning('Travel Insurance Confirmation Failed', [
                        'insurance_id' => $insurance->id,
                        'error' => $confirmResult['error'] ?? 'Unknown error'
                    ]);

                    $insurance->update([
                        'payment_status' => 'paid',
                        'status' => 'failed'
                    ]);

                    $this->sendFailureEmails($insurance);

                    $errorMessage = 'Your payment was successful, but we could not confirm your insurance: ' . ($confirmResult['error'] ?? 'Unknown error');

                    return view('frontend.travel-insurance.payment-failed', compact('insurance'))
                        ->with('notify_error', $errorMessage)
                        ->with('notify_error', $errorMessage);
                }

                // Send success emails only if confirmed
                $this->sendSuccessEmails($insurance);

                return view('frontend.travel-insurance.payment-success', compact('insurance'));
            } else {
                $insurance->update([
                    'payment_status' => 'failed',
                    'payment_response' => json_encode($verification)
                ]);

                // Send failure emails
                $this->sendFailureEmails($insurance);

                $errorMessage = 'Payment verification failed: ' . ($verification['error'] ?? 'Unknown error');

                return view('frontend.travel-insurance.payment-failed', compact('insurance'))
                    ->with('notify_error', $errorMessage)
                    ->with('notify_error', $errorMessage);
            }
        } catch (\Exception $e) {
            Log::error('Travel Insurance Payment Success Error', [
                'insurance_id' => $insurance,
                'error' => $e->getMessage()
            ]);

            $errorMessage = 'An error occurred while verifying your payment: ' . $e->getMessage();

            return view('frontend.travel-insurance.payment-failed', ['insurance' => null])
                ->with('notify_error', $errorMessage)
                ->with('notify_error', $errorMessage);
        }
    }

    public function paymentFailed(Request $request)
    {
        $insuranceId = $request->input('insurance');
        $insurance = null;

        if ($insuranceId) {
            $insurance = TravelInsurance::find($insuranceId);
            if ($insurance) {
                $insurance->update([
                    'payment_status' => 'failed',
                    'status' => 'cancelled'
                ]);

                // Send failure emails
                $this->sendFailureEmails($insurance);
            }
        }

        return view('frontend.travel-insurance.payment-failed', compact('insurance'));
    }

    protected function sendSuccessEmails(TravelInsurance $insurance)
    {
        try {
            $commissionPercentage = $this->insuranceCommissionPercentage / 100;

            // Send email to user
            Mail::send('emails.insurance-success-user', compact('insurance', 'commissionPercentage'), function ($message) use ($insurance) {
                $message->to($insurance->lead_email, $insurance->lead_name)
                    ->subject('Travel Insurance Confirmed - ' . $insurance->insurance_number);

                // Attach PDF files if available
                $passengers = $insurance->passengers;
                foreach ($passengers as $passenger) {
                    if ($passenger->policy_url_link) {
                        try {
                            $pdfContent = file_get_contents($passenger->policy_url_link);
                            if ($pdfContent) {
                                $message->attachData($pdfContent, 'policy_' . $passenger->policy_number . '.pdf', [
                                    'mime' => 'application/pdf',
                                ]);
                            }
                        } catch (\Exception $e) {
                            Log::warning('Failed to attach policy PDF', [
                                'passenger_id' => $passenger->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            });

            // Send email to admin
            Mail::send('emails.insurance-success-admin', compact('insurance', 'commissionPercentage'), function ($message) use ($insurance) {
                $message->to($this->adminEmail)
                    ->subject('New Insurance Sale - ' . $insurance->insurance_number);
            });

            Log::info('Insurance success emails sent', ['insurance_id' => $insurance->id]);
        } catch (\Exception $e) {
            Log::error('Failed to send insurance success emails', [
                'insurance_id' => $insurance->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function sendFailureEmails(TravelInsurance $insurance)
    {
        try {
            $commissionPercentage = $this->insuranceCommissionPercentage / 100;

            // Send email to user
            Mail::send('emails.insurance-failed-user', compact('insurance', 'commissionPercentage'), function ($message) use ($insurance) {
                $message->to($insurance->lead_email, $insurance->lead_name)
                    ->subject('Payment Failed - ' . $insurance->insurance_number);
            });

            // Send email to admin
            Mail::send('emails.insurance-failed-admin', compact('insurance', 'commissionPercentage'), function ($message) use ($insurance) {
                $message->to($this->adminEmail)
                    ->subject('Insurance Payment Failed - ' . $insurance->insurance_number);
            });

            Log::info('Insurance failure emails sent', ['insurance_id' => $insurance->id]);
        } catch (\Exception $e) {
            Log::error('Failed to send insurance failure emails', [
                'insurance_id' => $insurance->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
