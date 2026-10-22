<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CvController extends Controller
{
    /**
     * Display a listing of CVs.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);
        $cvs = Cv::orderBy('created_at', 'desc')->paginate((int) ($validated['per_page'] ?? 15));

        return response()->json($cvs);
    }

    /**
     * Store a newly created CV.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'version'     => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active'   => ['sometimes', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true)) {
                    $fail('The active status must be true or false.');
                }
            }],
            'file'        => 'required|file|mimes:pdf,doc,docx|max:10240', // 10MB
        ]);

        $validated['is_active'] = filter_var($validated['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // Handle file upload
        $file     = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $filePath = $file->store('cvs', 'public');
        $fileSize = $file->getSize();

        // Create CV record
        $cv = Cv::create([
            'title'       => $validated['title'],
            'file_name'   => $fileName,
            'file_path'   => $filePath,
            'file_size'   => $fileSize,
            'version'     => $validated['version'] ?? '1.0',
            'description' => $validated['description'] ?? null,
            'is_active'   => $validated['is_active'] ?? false,
        ]);

        // If set as active, deactivate others
        if ($cv->is_active) {
            $cv->setAsActive();
        }

        return response()->json([
            'message' => 'CV uploaded successfully',
            'cv'      => $cv,
        ], 201);
    }

    /**
     * Display the specified CV.
     */
    public function show(Cv $cv)
    {
        return response()->json($cv);
    }

    /**
     * Update the specified CV.
     */
    public function update(Request $request, Cv $cv)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'version'     => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active'   => ['sometimes', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true)) {
                    $fail('The active status must be true or false.');
                }
            }],
            'file'        => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        if (array_key_exists('is_active', $validated)) {
            $validated['is_active'] = filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        // Handle file replacement if new file uploaded
        if ($request->hasFile('file')) {
            // Delete old file
            Storage::disk('public')->delete($cv->file_path);

            // Upload new file
            $file     = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $filePath = $file->store('cvs', 'public');
            $fileSize = $file->getSize();

            $validated['file_name'] = $fileName;
            $validated['file_path'] = $filePath;
            $validated['file_size'] = $fileSize;
        }

        // Update CV
        $cv->update($validated);

        // If set as active, deactivate others
        if ($cv->is_active) {
            $cv->setAsActive();
        }

        return response()->json([
            'message' => 'CV updated successfully',
            'cv'      => $cv->fresh(),
        ]);
    }

    /**
     * Remove the specified CV.
     */
    public function destroy(Cv $cv)
    {
        $cv->delete(); // File deletion handled in model boot method

        return response()->json([
            'message' => 'CV deleted successfully',
        ]);
    }

    /**
     * Set a CV as active.
     */
    public function setActive(Cv $cv)
    {
        $cv->setAsActive();

        return response()->json([
            'message' => 'CV set as active successfully',
            'cv'      => $cv->fresh(),
        ]);
    }

    /**
     * Get the active CV.
     */
    public function getActive()
    {
        $activeCv = Cv::active()->first();

        if (! $activeCv) {
            return response()->json([
                'message' => 'No active CV found',
            ], 404);
        }

        return response()->json($activeCv);
    }
}
