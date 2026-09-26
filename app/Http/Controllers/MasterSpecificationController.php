<?php

namespace App\Http\Controllers;

use App\Engines\Sales\Models\BuilderSession;
use App\Engines\Sales\Models\MasterSpecification;
use App\Engines\Sales\Models\ProjectRequest;
use App\Engines\Sales\Services\BuilderSessionService;
use App\Engines\Sales\Services\MasterSpecificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MasterSpecificationController extends Controller
{
    public function show(Request $request, BuilderSessionService $builderService, MasterSpecificationService $specifications): View|RedirectResponse
    {
        $builder = $builderService->current($request);
        $client = $request->user('client');
        $specifications->assertOwner($builder, $client);
        $latest = fn () => ProjectRequest::query()->where('builder_session_id', $builder->id)->first()?->specifications()->latest('version')->first();

        // Dijana automatik: tiada spec lagi, atau draf dikemas kini ikut jawapan Builder terkini. Versi diluluskan tidak disentuh.
        $specification = $latest();
        if (! $specification || $specification->status === MasterSpecification::STATUS_DRAFT) {
            try {
                $specification = $specifications->generateDraft($builder, $client);
            } catch (ValidationException $e) {
                return redirect()->route('builder.start')->with('builder_status', collect($e->errors())->flatten()->first());
            }
        }

        return view('sales.specification', ['builderSession' => $builder, 'specification' => $specification]);
    }

    public function generate(Request $request, BuilderSessionService $builderService, MasterSpecificationService $specifications): RedirectResponse
    {
        $builder = $builderService->current($request);
        $specifications->generateDraft($builder, $request->user('client'));

        return redirect()->route('specification.show')->with('specification_status', 'Master Specification Draft telah dijana.');
    }

    public function approve(Request $request, BuilderSessionService $builderService, MasterSpecificationService $specifications, MasterSpecification $specification): RedirectResponse
    {
        $builder = $builderService->current($request);
        $specifications->approve($specification, $builder, $request->user('client'));

        $quotation = \App\Engines\Sales\Models\Quotation::query()->where('master_specification_id', $specification->id)->first();
        if ($quotation && in_array($quotation->status, [\App\Engines\Sales\Models\Quotation::STATUS_SENT, \App\Engines\Sales\Models\Quotation::STATUS_VIEWED], true)) {
            return redirect()->route('client.quotations.show', $quotation)->with('status', 'Master Specification diluluskan. Quotation anda telah dijana — semak dan terima untuk teruskan ke bayaran.');
        }

        return redirect()->route('specification.show')->with('specification_status', 'Master Specification telah diluluskan dan dikunci. Quotation sedang disediakan oleh pasukan kami.');
    }
}
