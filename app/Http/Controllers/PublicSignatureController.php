<?php

namespace App\Http\Controllers;

use App\Models\PublicSignature;
use Illuminate\View\View;

class PublicSignatureController extends Controller
{
    public function show(string $id): View
    {
        $signature = PublicSignature::with('document.documentType', 'document.organization')
            ->find($id);

        abort_if(! $signature, 404);

        return view('pages.public.signature', [
            'signature' => $signature,
        ]);
    }
}
