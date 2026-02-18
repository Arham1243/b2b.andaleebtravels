<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\B2bInquiry;

class InquiryController extends Controller
{
    public function index()
    {
        $contacts = B2bInquiry::latest()->get();
        return view('admin.inquiries.list', compact('contacts'));
    }

    public function destroy(B2bInquiry $contact)
    {
        $contact->delete();
        return redirect()->route('admin.inquiries.index')->with('notify_success', 'Contact deleted successfully!');
    }
}
