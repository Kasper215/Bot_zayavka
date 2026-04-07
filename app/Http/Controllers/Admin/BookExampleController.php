<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookExample;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BookExampleController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/BookExamples/Index', [
            'examples' => BookExample::orderBy('order_index')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tag' => 'nullable|string|max:50',
            'cover' => 'required|image|max:5120',
            'pdf' => 'required|file|mimes:pdf|max:51200',
            'order_index' => 'nullable|integer',
        ]);

        $coverPath = $request->file('cover')->store('examples/covers', 'public');
        $pdfPath = $request->file('pdf')->store('examples/pdfs', 'public');

        BookExample::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'tag' => $validated['tag'],
            'cover_path' => '/storage/' . $coverPath,
            'pdf_path' => '/storage/' . $pdfPath,
            'order_index' => $validated['order_index'] ?? 0,
            'is_visible' => true,
        ]);

        return back()->with('success', 'Пример книги добавлен');
    }

    public function update(Request $request, BookExample $example)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tag' => 'nullable|string|max:50',
            'cover' => 'nullable|image|max:5120',
            'pdf' => 'nullable|file|mimes:pdf|max:51200',
            'order_index' => 'nullable|integer',
            'is_visible' => 'required|boolean',
        ]);

        $data = [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'tag' => $validated['tag'],
            'order_index' => $validated['order_index'] ?? 0,
            'is_visible' => $validated['is_visible'],
        ];

        if ($request->hasFile('cover')) {
            $coverPath = $request->file('cover')->store('examples/covers', 'public');
            $data['cover_path'] = '/storage/' . $coverPath;
        }

        if ($request->hasFile('pdf')) {
            $pdfPath = $request->file('pdf')->store('examples/pdfs', 'public');
            $data['pdf_path'] = '/storage/' . $pdfPath;
        }

        $example->update($data);

        return back()->with('success', 'Пример книги обновлен');
    }

    public function destroy(BookExample $example)
    {
        $example->delete();
        return back()->with('success', 'Пример книги удален');
    }
}
