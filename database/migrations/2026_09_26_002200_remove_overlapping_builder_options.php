<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Pilihan bertindih dengan soalan "Fungsi" (yang kini menentukan add-on berbayar) dibuang daripada soalan Tujuan & Maklumat. */
    private const REMOVE = ['website_purpose' => ['portfolio', 'booking'], 'website_information' => ['portfolio', 'testimonials', 'blog-news']];

    public function up(): void
    {
        foreach (self::REMOVE as $question => $codes) {
            $id = DB::table('builder_questions')->where('code', $question)->value('id');
            if ($id) {
                DB::table('builder_question_options')->where('builder_question_id', $id)->whereIn('code', $codes)->delete();
            }
        }
    }

    public function down(): void
    {
        // Pilihan dicipta semula oleh BuilderQuestionSeeder versi terdahulu jika perlu.
    }
};
