<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;

class InquiryController extends Controller
{
    public function index()
    {
        $contacts = Inquiry::latest()->get();
        return view('admin.inquiries.list', compact('contacts'));
    }

    public function destroy(Inquiry $contact)
    {
        $contact->delete();
        return redirect()->route('admin.inquiries.index')->with('notify_success', 'Contact deleted successfully!');
    }
}
