<?php
namespace App\Http\Controllers;

use App\Models\ContactSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicController extends Controller
{
    public function home(): View
    {
        return view('public.home');
    }

    public function about(): View
    {
        return view('public.about');
    }

    public function features(): View
    {
        return view('public.features');
    }

    public function services(): View
    {
        return view('public.services');
    }

    public function faq(): View
    {
        return view('public.faq');
    }

    public function contact(): View
    {
        return view('public.contact');
    }

    public function submitContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        ContactSubmission::create($validated);

        return back()->with('status', "Thanks, {$validated['name']} — we've received your message and will get back to you soon.");
    }
}