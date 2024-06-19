<?php

namespace App\Http\Controllers;

use App\Mail\JobNotificationEmail;
use App\Models\Category;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobType;
use App\Models\SavedJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class JobsController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::where('status', 1)->get();
        $jobTypes = JobType::where('status', 1)->get();
        $jobs = Job::where('status', 1);

        // Search using keywords
        if (!empty($request->keyword)) {
            $jobs = $jobs->where(function ($query) use ($request) {
                $query->where('title', 'like', '%' . $request->keyword . '%')
                    ->orWhere('keywords', 'like', '%' . $request->keyword . '%');
            });
        }

        // Search using location
        if (!empty($request->location)) {
            $jobs = $jobs->where('location', $request->location);
        }

        // Search using category
        if (!empty($request->category)) {
            $jobs = $jobs->where('category_id', $request->category);
        }

        $jobTypeArray = [];

        // Search using job type
        if (!empty($request->jobType)) {
            $jobTypeArray = explode(',', $request->jobType);
            $jobs = $jobs->whereIn('job_type_id', $jobTypeArray);
        }

        // Search using experience
        if (!empty($request->experience)) {
            $jobs = $jobs->where('experience', $request->experience);
        }

        $jobs = $jobs->with(['jobType', 'category']);

        if (!empty($request->sort) && $request->sort == '0') {

            $jobs = $jobs->orderBy('created_at', 'ASC');
        } else {
            $jobs = $jobs->orderBy('created_at', 'DESC');
        }


        $jobs = $jobs->paginate(9);

        return view('front.jobs', [
            'categories' => $categories,
            'jobTypes' => $jobTypes,
            'jobs' => $jobs,
            'jobTypeArray' => $jobTypeArray,
        ]);
    }

    public function detail($id)
    {

        $job = Job::where([
            'id' => $id,
            'status' => 1,
        ])->with('jobType', 'category')->first();

        if ($job == null) {
            abort(404);
        }
        $count = 0;
        if (Auth::user()) {
            # code...

            $count = SavedJob::where([
                'user_id' => Auth::user()->id,
                'job_id' => $id,
                ])->count();
            }

                //fetch applications
                $applications = JobApplication::where('job_id', $id)->with('user')->get();

            return view('front.jobDetail', [
                    'job' => $job,
                    'count' => $count,
                    'applications' => $applications,
                ]);
    }
    public function applyJob(Request $request)
    {
        $id = $request->id;
        $job = Job::where('id', $id)->first();

        if ($job == null) {
            $message = 'Job does not exist';

            session()->flash('error', $message);
            return response()->json([
                'status' => false,
                'message' => $message,
            ]);
        }

        //you cant apply on your jobs
        $employer_id = $job->user_id;

        if ($employer_id == Auth::user()->id) {
            $message = 'You can not apply on own job';

            session()->flash('error', $message);
            return response()->json([
                'status' => false,
                'message' => $message,
            ]);
        }
        //you can not apply on a job twice
        $jobApplicationCount = JobApplication::where([
            'user_id' => Auth::user()->id,
            'job_id' => $id,
        ])->count();

        if ($jobApplicationCount > 0) {
            $message = 'You have already applied on this job';
            session()->flash('error', $message);
            return response()->json([
                'status' => false,
                'message' => $message,
            ]);
        }


        $application = new JobApplication();
        $application->job_id = $id;
        $application->user_id = Auth::user()->id;
        $application->employer_id = $employer_id;
        $application->applied_data = now();
        $application->save();

        // send notification to employer
        $employer = User::where('id', $employer_id)->first();
        $mailData = [
            'employer' => $employer,
            'user' => Auth::user(),
            'job' => $job,
        ];
        Mail::to($employer->email)->send(new JobNotificationEmail($mailData));

        $message = 'You have applied successfully';
        session()->flash('success', $message);
        return response()->json([
            'status' => true,
            'message' => $message,
        ]);
    }
    public function saveJob(Request $request)
    {
        $id = $request->id;

        $job = Job::find($id);
        if ($job == null) {
            $message = 'Job not found';
            session()->flash('error',$message );
            return response()->json([
                'status' => false,
                'message' => $message,
            ]);
        }
        //check if user has laready saved the job

        $count = SavedJob::where([
            'user_id' => Auth::user()->id,
            'job_id' => $id,
        ])->count();
        if ($count > 0) {
            $message = 'You have already saved this job';
            session()->flash('error', $message);
            return response()->json([
                'status' => false,
                'message' => $message,

            ]);
        }
        $savedJob = new SavedJob();
        $savedJob->user_id = Auth::user()->id;
        $savedJob->job_id = $id;
        $savedJob->save();
        $message = 'Job saved successfully';
        $session = session()->flash('success', $message);
        return response()->json([
            'status' => true,
            'message' => $message,
            ]);


    }
}
