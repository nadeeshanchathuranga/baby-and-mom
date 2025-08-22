<?php

namespace App\Http\Controllers;

use App\Models\CompanyInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CompanyInfoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!Gate::allows('hasRole', ['Admin', 'Manager'])) {
            abort(403, 'Unauthorized');
        }

        $companyInfo = CompanyInfo::first();

        return Inertia::render('CompanyInfo/Index', [
            'companyInfo' => $companyInfo,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     */
    public function show(CompanyInfo $companyInfo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CompanyInfo $companyInfo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */



    public function update(Request $request, CompanyInfo $companyInfo)
    {

    
        if (!Gate::allows('hasRole', ['Admin'])) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name'    => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone'   => ['nullable','string','regex:/^\d{10}$/'],
            'phone2'  => ['nullable','string','regex:/^\d{10}$/'],
            'email'   => ['nullable','email','max:255','regex:/^[\w\.-]+@[a-zA-Z0-9\.-]+\.[a-zA-Z]{2,6}$/'],
            'website' => 'nullable|string|max:255',
            'logo'    => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Remove old file if exists
            if ($companyInfo->logo) {
                // convert "storage/CompanyInfos/xxx" -> "CompanyInfos/xxx"
                $oldPath = str_replace('storage/', '', $companyInfo->logo);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $file = $request->file('logo');
            $fileExtension = $file->getClientOriginalExtension();
            $fileName = 'companyInfo_' . now()->format('YmdHis') . '.' . $fileExtension;

            // store to public disk
            $path = $file->storeAs('CompanyInfos', $fileName, 'public');

            // save public URL path
            $validated['logo'] = 'storage/' . $path;
        } else {
            // Keep existing logo if not replaced
            $validated['logo'] = $companyInfo->logo;
        }

        $companyInfo->update($validated);

        // If your route name is different, adjust it; using your original:
        return redirect()
            ->route('companyInfo.index') // or ->route('company-info.index') if that is your actual route
            ->with('banner', 'Company info updated successfully');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CompanyInfo $companyInfo)
    {
        //
    }
}
