<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        
        // Get the user's latest avatar media
        $avatarMedia = $user->getFirstMedia('avatar');
        
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            
            // Add avatar and media information
            'media' => $avatarMedia ? [
                'id' => $avatarMedia->id,
                'name' => $avatarMedia->name,
                'file_name' => $avatarMedia->file_name,
                'mime_type' => $avatarMedia->mime_type,
                'size' => $avatarMedia->size,
                'original_url' => $avatarMedia->getUrl(),
                'preview_url' => $avatarMedia->hasGeneratedConversion('thumb') 
                    ? $avatarMedia->getUrl('thumb')
                    : $avatarMedia->getUrl(),
                'created_at' => $avatarMedia->created_at,
                'updated_at' => $avatarMedia->updated_at,
            ] : null,
            
            // Add avatar information
            'avatar' => [
                'url' => $user->avatar_url, // Uses the accessor from the User model
                'permissions' => [
                    'canUpload' => true, // You can add specific permissions here
                    'maxSize' => config('media-library.max_file_size', 10 * 1024 * 1024), // 10MB default
                    'acceptedTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                ],
            ],
            
            // Add preferences and settings
            'preferences' => [
                'date_format' => $user->date_format ?? 'YYYY-MM-DD',
                'time_format' => $user->time_format ?? '24',
                'timezone' => $user->timezone ?? config('app.timezone'),
                'language' => $user->language ?? config('app.locale'),
            ],
            
            // Add available options for preferences
            'availableOptions' => [
                'timezones' => array_map(function($timezone) {
                    return [
                        'value' => $timezone,
                        'label' => str_replace('_', ' ', $timezone)
                    ];
                }, timezone_identifiers_list()),
                
                'languages' => [
                    ['value' => 'en', 'label' => 'English'],
                    ['value' => 'es', 'label' => 'Español'],
                    ['value' => 'fr', 'label' => 'Français'],
                    // Add more languages as needed
                ],
                
                'dateFormats' => [
                    ['value' => 'YYYY-MM-DD', 'label' => date('Y-m-d')],
                    ['value' => 'DD/MM/YYYY', 'label' => date('d/m/Y')],
                    ['value' => 'MM/DD/YYYY', 'label' => date('m/d/Y')],
                    ['value' => 'DD.MM.YYYY', 'label' => date('d.m.Y')],
                ],
                
                'timeFormats' => [
                    ['value' => '12', 'label' => '12-hour'],
                    ['value' => '24', 'label' => '24-hour'],
                ],
            ],
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        
        // Fill basic information
        $user->fill($request->validated());

        // Handle email verification requirement
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // Handle avatar upload if provided
        if ($request->hasFile('avatar')) {
            $user->clearMediaCollection('avatar');
            $user->addMediaFromRequest('avatar')
                ->usingFileName(uniqid() . '.' . $request->file('avatar')->extension())
                ->toMediaCollection('avatar');
        }

        // Save all changes
        $user->save();

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Clear media before deleting user
        $user->clearMediaCollection('avatar');

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}