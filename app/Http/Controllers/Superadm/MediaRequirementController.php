<?php

namespace App\Http\Controllers\Superadm;

use App\Http\Controllers\Controller;
use App\Models\MediaRequirement;
use Illuminate\Http\Request;

/**
 * Requirements the media team works from.
 *
 * Follows the Contact Us screen's shape — list, view, delete — so it sits in
 * the existing admin without introducing a second way of doing things.
 */
class MediaRequirementController extends Controller
{
    public function index()
    {
        $requirements = MediaRequirement::orderByDesc('id')->get();

        return view('superadm.requirements.list', compact('requirements'));
    }

    public function viewDetails($encodedId)
    {
        // base64 ids in the URL, as the other admin detail screens do.
        $requirement = MediaRequirement::findOrFail(base64_decode($encodedId));

        return view('superadm.requirements.view-details', compact('requirement'));
    }

    public function updateStatus(Request $request)
    {
        $data = $request->validate([
            'id'     => 'required|integer|exists:media_requirements,id',
            'status' => 'required|in:new,in_progress,closed',
        ]);

        MediaRequirement::where('id', $data['id'])->update(['status' => $data['status']]);

        return back()->with('success', 'Requirement status updated.');
    }

    public function delete(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        MediaRequirement::where('id', $request->id)->delete();

        return back()->with('success', 'Requirement deleted.');
    }
}
