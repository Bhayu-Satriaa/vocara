<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatbotKnowledge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengetahuanChatbotController extends Controller
{
    public function index(): View
    {
        $pengetahuan = ChatbotKnowledge::query()
            ->orderBy('kategori')
            ->orderBy('pertanyaan')
            ->paginate(15);

        return view('admin.pengetahuan-chatbot.index', ['pengetahuan' => $pengetahuan]);
    }

    public function create(): View
    {
        return view('admin.pengetahuan-chatbot.create', [
            'item' => new ChatbotKnowledge(['is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $data['updated_by'] = $request->user()->id;

        ChatbotKnowledge::create($data);

        return redirect()->route('admin.pengetahuan-chatbot.index')
            ->with('sukses', 'Pengetahuan chatbot berhasil ditambahkan.');
    }

    public function edit(ChatbotKnowledge $pengetahuan): View
    {
        return view('admin.pengetahuan-chatbot.edit', ['item' => $pengetahuan]);
    }

    public function update(Request $request, ChatbotKnowledge $pengetahuan): RedirectResponse
    {
        $data = $this->validasi($request);
        $data['updated_by'] = $request->user()->id;

        $pengetahuan->update($data);

        return redirect()->route('admin.pengetahuan-chatbot.index')
            ->with('sukses', 'Pengetahuan chatbot diperbarui.');
    }

    public function destroy(ChatbotKnowledge $pengetahuan): RedirectResponse
    {
        $pengetahuan->delete();

        return redirect()->route('admin.pengetahuan-chatbot.index')
            ->with('sukses', 'Pengetahuan chatbot dihapus.');
    }

    private function validasi(Request $request): array
    {
        $data = $request->validate([
            'kategori' => ['required', 'string', 'max:50'],
            'pertanyaan' => ['required', 'string', 'max:255'],
            'jawaban' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
