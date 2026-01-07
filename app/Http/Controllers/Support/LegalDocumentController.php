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
        $documents = LegalDocument::termsAndConditions()
            ->with('creator:id,name')
            ->latest()
            ->get()
            ->map(fn ($doc) => [
                ...$doc->toArray(),
                'creator_name' => $doc->creator?->name ?? 'Sistema',
            ]);

        $published = $documents->firstWhere('is_published', true);

        return Inertia::render('support/terms-and-conditions/index', [
            'documents' => $documents,
            'published' => $published,
            'stats' => [
                'total' => $documents->count(),
                'published' => $published ? 1 : 0,
            ],
        ]);
    }

    public function termsCreate(): Response
    {
        $latestVersion = LegalDocument::generateNextVersion('terms_and_conditions');
        $latestContent = LegalDocument::termsAndConditions()->latest()->first();

        return Inertia::render('support/terms-and-conditions/edit', [
            'document' => null,
            'suggestedVersion' => $latestVersion,
            'latestContent' => $latestContent?->content_json,
        ]);
    }

    public function termsStore(StoreLegalDocumentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['type'] = 'terms_and_conditions';
        $data['created_by'] = auth()->id();
        $data['version'] = $data['version'] ?? LegalDocument::generateNextVersion('terms_and_conditions');

        $document = LegalDocument::create($data);

        if ($request->boolean('publish')) {
            $document->publish();
        }

        return redirect()->route('support.terms.index')
            ->with('success', 'Términos y Condiciones guardados exitosamente.');
    }

    public function termsEdit(LegalDocument $document): Response
    {
        if ($document->type !== 'terms_and_conditions') {
            abort(404);
        }

        return Inertia::render('support/terms-and-conditions/edit', [
            'document' => $document,
            'suggestedVersion' => LegalDocument::generateNextVersion('terms_and_conditions'),
        ]);
    }

    public function termsUpdate(StoreLegalDocumentRequest $request, LegalDocument $document): RedirectResponse
    {
        if ($document->type !== 'terms_and_conditions') {
            abort(404);
        }

        $data = $request->validated();
        $document->update($data);

        if ($request->boolean('publish')) {
            $document->publish();
        }

        return redirect()->route('support.terms.index')
            ->with('success', 'Términos y Condiciones actualizados exitosamente.');
    }

    public function termsDestroy(LegalDocument $document): RedirectResponse
    {
        if ($document->type !== 'terms_and_conditions') {
            abort(404);
        }

        if ($document->is_published) {
            return redirect()->back()
                ->with('error', 'No puedes eliminar el documento publicado.');
        }

        $document->delete();

        return redirect()->route('support.terms.index')
            ->with('success', 'Documento eliminado exitosamente.');
    }

    public function termsPublish(LegalDocument $document): RedirectResponse
    {
        if ($document->type !== 'terms_and_conditions') {
            abort(404);
        }

        $document->publish();

        return redirect()->route('support.terms.index')
            ->with('success', 'Documento publicado exitosamente.');
    }

    public function privacyIndex(): Response
    {
        $documents = LegalDocument::privacyPolicy()
            ->with('creator:id,name')
            ->latest()
            ->get()
            ->map(fn ($doc) => [
                ...$doc->toArray(),
                'creator_name' => $doc->creator?->name ?? 'Sistema',
            ]);

        $published = $documents->firstWhere('is_published', true);

        return Inertia::render('support/privacy-policy/index', [
            'documents' => $documents,
            'published' => $published,
            'stats' => [
                'total' => $documents->count(),
                'published' => $published ? 1 : 0,
            ],
        ]);
    }

    public function privacyCreate(): Response
    {
        $latestVersion = LegalDocument::generateNextVersion('privacy_policy');
        $latestContent = LegalDocument::privacyPolicy()->latest()->first();

        return Inertia::render('support/privacy-policy/edit', [
            'document' => null,
            'suggestedVersion' => $latestVersion,
            'latestContent' => $latestContent?->content_json,
        ]);
    }

    public function privacyStore(StoreLegalDocumentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['type'] = 'privacy_policy';
        $data['created_by'] = auth()->id();
        $data['version'] = $data['version'] ?? LegalDocument::generateNextVersion('privacy_policy');

        $document = LegalDocument::create($data);

        if ($request->boolean('publish')) {
            $document->publish();
        }

        return redirect()->route('support.privacy.index')
            ->with('success', 'Política de Privacidad guardada exitosamente.');
    }

    public function privacyEdit(LegalDocument $document): Response
    {
        if ($document->type !== 'privacy_policy') {
            abort(404);
        }

        return Inertia::render('support/privacy-policy/edit', [
            'document' => $document,
            'suggestedVersion' => LegalDocument::generateNextVersion('privacy_policy'),
        ]);
    }

    public function privacyUpdate(StoreLegalDocumentRequest $request, LegalDocument $document): RedirectResponse
    {
        if ($document->type !== 'privacy_policy') {
            abort(404);
        }

        $data = $request->validated();
        $document->update($data);

        if ($request->boolean('publish')) {
            $document->publish();
        }

        return redirect()->route('support.privacy.index')
            ->with('success', 'Política de Privacidad actualizada exitosamente.');
    }

    public function privacyDestroy(LegalDocument $document): RedirectResponse
    {
        if ($document->type !== 'privacy_policy') {
            abort(404);
        }

        if ($document->is_published) {
            return redirect()->back()
                ->with('error', 'No puedes eliminar el documento publicado.');
        }

        $document->delete();

        return redirect()->route('support.privacy.index')
            ->with('success', 'Documento eliminado exitosamente.');
    }

    public function privacyPublish(LegalDocument $document): RedirectResponse
    {
        if ($document->type !== 'privacy_policy') {
            abort(404);
        }

        $document->publish();

        return redirect()->route('support.privacy.index')
            ->with('success', 'Documento publicado exitosamente.');
    }
}
