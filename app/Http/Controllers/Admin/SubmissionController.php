<?php

namespace App\Http\Controllers\Admin;

use Auth;
use Config;
use Illuminate\Http\Request;

use App\Models\Submission\Submission;
use App\Models\Item\Item;
use App\Models\Currency\Currency;
use App\Models\Loot\LootTable;

use App\Services\SubmissionManager;

use App\Http\Controllers\Controller;

class SubmissionController extends Controller
{
    /**
     * Shows the submission index page.
     *
     * @param  string  $status
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getSubmissionIndex($status = null)
    {
        return view('admin.submissions.index', [
            'submissions' => Submission::where('status', $status ? ucfirst($status) : 'Pending')->whereNotNull('prompt_id')->orderBy('id', 'DESC')->paginate(30),
            'isClaims' => false
        ]);
    }
    
    /**
     * Shows the submission detail page.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getSubmission($id)
    {
        $submission = Submission::whereNotNull('prompt_id')->where('id', $id)->first();
        if(!$submission) abort(404);
        return view('admin.submissions.submission', [
            'submission' => $submission,
        ] + ($submission->status == 'Pending' ? [
            'characterCurrencies' => Currency::where('is_character_owned', 1)->orderBy('sort_character', 'DESC')->pluck('name', 'id'),
            'items' => Item::orderBy('name')->pluck('name', 'id'),
            'currencies' => Currency::where('is_user_owned', 1)->orderBy('name')->pluck('name', 'id'),
            'tables' => LootTable::orderBy('name')->pluck('name', 'id'),
            'count' => Submission::where('prompt_id', $id)->where('status', 'Approved')->where('user_id', $submission->user_id)->count()
        ] : []));
    }    
    
    /**
     * Shows the claim index page.
     *
     * @param  string  $status
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getClaimIndex($status = null)
    {
        return view('admin.submissions.index', [
            'submissions' => Submission::where('status', $status ? ucfirst($status) : 'Pending')->whereNull('prompt_id')->orderBy('id', 'DESC')->paginate(30),
            'isClaims' => true
        ]);
    }
    
    /**
     * Shows the claim detail page.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getClaim($id)
    {
        $submission = Submission::whereNull('prompt_id')->where('id', $id)->first();
        if(!$submission) abort(404);
        return view('admin.submissions.submission', [
            'submission' => $submission,
        ] + ($submission->status == 'Pending' ? [
            'characterCurrencies' => Currency::where('is_character_owned', 1)->orderBy('sort_character', 'DESC')->pluck('name', 'id'),
            'items' => Item::orderBy('name')->pluck('name', 'id'),
            'currencies' => Currency::where('is_user_owned', 1)->orderBy('name')->pluck('name', 'id'),
            'tables' => LootTable::orderBy('name')->pluck('name', 'id'),
            'count' => Submission::where('prompt_id', $id)->where('status', 'Approved')->where('user_id', $submission->user_id)->count()
        ] : []));
    }

    /**
     * Creates a new submission.
     *
     * @param  \Illuminate\Http\Request        $request
     * @param  App\Services\SubmissionManager  $service
     * @param  int                             $id
     * @param  string                          $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postSubmission(Request $request, SubmissionManager $service, $id, $action)
    {
        $data = $request->only(['slug',  'character_quantity', 'character_currency_id', 'rewardable_type', 'rewardable_id', 'quantity' ]);
        if($action == 'reject' && $service->rejectSubmission($request->only(['staff_comments']) + ['id' => $id], Auth::user())) {
            flash('Submission rejected successfully.')->success();
        }
        elseif($action == 'approve' && $service->approveSubmission($data + ['id' => $id], Auth::user())) {
            flash('Submission approved successfully.')->success();
        }
        else {
            foreach($service->errors()->getMessages()['error'] as $error) flash($error)->error();
        }
        return redirect()->back();
    }
}