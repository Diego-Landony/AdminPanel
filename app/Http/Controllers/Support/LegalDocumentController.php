<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\StoreLegalDocumentRequest;
use App\Models\LegalDocument;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LegalDocumentController extends Controller
{
    public function termsIndex(): Response
    {
        $document = LegalDocument::termsAndConditions()
            ->published()
            ->first();

        // Si no hay documento publicado, obtener el más reciente
        if (! $document) {
            $document = LegalDocument::termsAndConditions()->latest()->first();
        }

        return Inertia::render('support/terms-and-conditions/index', [
            'document' => $document,
        ]);
    }

    public function termsEdit(): Response
    {
        $document = LegalDocument::termsAndConditions()
            ->published()
            ->first();

        // Si no hay documento publicado, obtener el más reciente
        if (! $document) {
            $document = LegalDocument::termsAndConditions()->latest()->first();
        }

        return Inertia::render('support/terms-and-conditions/edit', [
            'document' => $document,
        ]);
    }

    public function termsUpdate(StoreLegalDocumentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['type'] = 'terms_and_conditions';
        $data['created_by'] = auth()->id();
        $data['version'] = '1.0';
        $data['is_published'] = true;
        $data['published_at'] = now();

        // Buscar documento existente o crear uno nuevo
        $document = LegalDocument::termsAndConditions()->first();

        if ($document) {
            $document->update($data);
        } else {
            LegalDocument::create($data);
        }

        return redirect()->route('support.terms.index')
            ->with('success', 'Términos y Condiciones actualizados exitosamente.');
    }

    public function privacyIndex(): Response
    {
        $document = LegalDocument::privacyPolicy()
            ->published()
            ->first();

        // Si no hay documento publicado, obtener el más reciente
        if (! $document) {
            $document = LegalDocument::privacyPolicy()->latest()->first();
        }

        return Inertia::render('support/privacy-policy/index', [
            'document' => $document,
        ]);
    }

    public function privacyEdit(): Response
    {
        $document = LegalDocument::privacyPolicy()
            ->published()
            ->first();

        // Si no hay documento publicado, obtener el más reciente
        if (! $document) {
            $document = LegalDocument::privacyPolicy()->latest()->first();
        }

        return Inertia::render('support/privacy-policy/edit', [
            'document' => $document,
        ]);
    }

    public function privacyUpdate(StoreLegalDocumentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['type'] = 'privacy_policy';
        $data['created_by'] = auth()->id();
        $data['version'] = '1.0';
        $data['is_published'] = true;
        $data['published_at'] = now();

        // Buscar documento existente o crear uno nuevo
        $document = LegalDocument::privacyPolicy()->first();

        if ($document) {
            $document->update($data);
        } else {
            LegalDocument::create($data);
        }

        return redirect()->route('support.privacy.index')
            ->with('success', 'Política de Privacidad actualizada exitosamente.');
    }
}
