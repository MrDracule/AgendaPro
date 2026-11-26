<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contact;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = auth()->user()->contacts();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $contacts = $query->latest()->get();

        return view('contacts.index', compact('contacts', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('contacts.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'type' => 'required|in:Pessoal,Trabalho,Outros',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
        ]);

        // Verifica se uma foto foi enviada
        if ($request->hasFile('photo')) {

            // Armazena a imagem no disco público
            $data['photo'] = $request->file('photo')->store('contacts', 'public');
        }

        // Cria o contato vinculado ao usuário logado
        auth()->user()->contacts()->create($data);

        return redirect()
            ->route('contacts.index')
            ->with('success', 'Contato cadastrado com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Contact $contact)
    {
        if ($contact->user_id !== auth()->id()) {
            abort(403, 'Acesso negado.');
        }

        return view('contacts.edit', compact('contact'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Contact $contact)
    {
        // Verifica se o contato pertence ao usuário logado
        if ($contact->user_id !== auth()->id()) {
            abort(403, 'Acesso negado.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'type' => 'required|in:Pessoal,Trabalho,Outros',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
        ]);

        // Verifica se uma nova foto foi enviada
        if ($request->hasFile('photo')) {

            // Exclui a foto anterior, se existir
            if ($contact->photo) {
                Storage::disk('public')->delete($contact->photo);
            }

            // Armazena a nova foto
            $data['photo'] = $request->file('photo')->store('contacts', 'public');
        }

        // Atualiza os dados do contato
        $contact->update($data);

        return redirect()
            ->route('contacts.index')
            ->with('update', 'Contato atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contact $contact)
    {
        if ($contact->user_id !== auth()->id()) {
            abort(403, 'Acesso negado.');
        }

        // Exclui a foto associada ao contato
        if ($contact->photo) {
            Storage::disk('public')->delete($contact->photo);
        }

        // Exclui o contato
        $contact->delete();

        return redirect()
            ->route('contacts.index')
            ->with('delete', 'Contato removido com sucesso!');
    }

    /**
     * Export contacts to PDF.
     */
    public function exportPdf()
    {
        ini_set('pcre.jit', '0');

        $contacts = auth()->user()->contacts()->latest()->get();

        $pdf = Pdf::loadView('contacts.pdf', compact('contacts'));

        return $pdf->download('minha_agenda.pdf');
    }
}
