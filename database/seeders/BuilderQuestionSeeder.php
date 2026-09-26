<?php

namespace Database\Seeders;

use App\Engines\Sales\Models\BuilderQuestion;
use App\Engines\Sales\Models\BuilderQuestionOption;
use Illuminate\Database\Seeder;

class BuilderQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            ['project_type', 'Apakah yang anda mahu bina?', 'single_choice', true, null, ['company-website' => 'Website syarikat', 'online-store' => 'Kedai online', 'promotion-landing-page' => 'Landing page promosi', 'business-management-system' => 'Sistem urus bisnes', 'ai-chatbot-automation' => 'AI / Chatbot / Automation', 'upgrade-existing' => 'Upgrade website/sistem sedia ada', 'unsure' => 'Saya tidak pasti']],
            ['website_purpose', 'Apakah tujuan utama website anda?', 'multiple_choice', false, ['project_type' => ['company-website']], ['introduce-company' => 'Memperkenalkan syarikat', 'show-services' => 'Menunjukkan servis', 'get-enquiry' => 'Mendapatkan enquiry', 'sell-products' => 'Menjual produk', 'other' => 'Lain-lain', 'unsure' => 'Saya tidak pasti']],
            ['website_functions', 'Fungsi apa yang anda perlukan?', 'multiple_choice', false, ['project_type' => ['company-website']], ['whatsapp' => 'WhatsApp', 'enquiry-form' => 'Borang enquiry', 'maps' => 'Maps', 'gallery' => 'Gallery', 'portfolio' => 'Portfolio', 'testimonials' => 'Testimonials', 'blog-news' => 'Blog / News', 'customer-login' => 'Customer login', 'online-payment' => 'Online payment', 'booking' => 'Booking', 'chatbot' => 'Chatbot', 'notifications' => 'Notifications', 'unsure' => 'Saya tidak pasti']],
            ['website_information', 'Maklumat apakah yang anda mahu paparkan?', 'multiple_choice', false, ['project_type' => ['company-website']], ['about' => 'About', 'services' => 'Services', 'products' => 'Products', 'faq' => 'FAQ', 'contact' => 'Contact', 'team' => 'Team', 'careers' => 'Careers', 'unsure' => 'Saya tidak pasti']],
            ['customer_login_method', 'Bagaimana pelanggan anda perlu log masuk?', 'single_choice', false, ['website_functions' => ['customer-login']], ['email-password' => 'Email + Password', 'email-otp' => 'Email + OTP', 'phone-sms-otp' => 'Phone / SMS OTP', 'recommend' => 'Cadangkan untuk saya']],
            ['ecommerce_product_quantity', 'Berapa jumlah produk yang anda ada?', 'single_choice', false, ['project_type' => ['online-store']], ['1-100' => '1–100', '101-500' => '101–500', '501-1500' => '501–1,500', 'more-than-1500' => 'Lebih daripada 1,500', 'unsure' => 'Saya tidak pasti']],
            ['ecommerce_functions', 'Fungsi kedai online yang diperlukan', 'multiple_choice', false, ['project_type' => ['online-store']], ['cart' => 'Cart', 'payment' => 'Payment', 'account' => 'Account', 'order-history' => 'Order history', 'variants' => 'Variants', 'stock' => 'Stock', 'promotion-coupon' => 'Promotion / coupon', 'shipping' => 'Shipping', 'reviews' => 'Reviews', 'wishlist' => 'Wishlist', 'multilingual' => 'Multilingual']],
            ['business_system_users', 'Siapa yang akan menggunakan sistem ini?', 'multiple_choice', false, ['project_type' => ['business-management-system']], ['owner' => 'Owner', 'staff' => 'Staff', 'customer' => 'Customer', 'supplier' => 'Supplier', 'agent' => 'Agent', 'management' => 'Management', 'other' => 'Lain-lain']],
            ['business_system_actions', 'Apa yang pengguna perlu lakukan?', 'multiple_choice', false, ['project_type' => ['business-management-system']], ['login' => 'Log masuk', 'save-data' => 'Simpan data', 'search-data' => 'Cari data', 'upload' => 'Upload', 'booking' => 'Booking', 'payment' => 'Payment', 'status' => 'Status', 'approval' => 'Approval', 'reporting' => 'Reporting', 'notification' => 'Notification']],
            ['ai_automation_solution', 'Apakah yang anda mahu AI atau automasi bantu?', 'multiple_choice', false, ['project_type' => ['ai-chatbot-automation']], ['customer-qa' => 'Customer Q&A', 'company-knowledge' => 'Company knowledge', 'telegram-bot' => 'Telegram bot', 'website-chatbot' => 'Website chatbot', 'workflow-automation' => 'Workflow automation', 'document-data-analysis' => 'Document / data analysis', 'ai-system-connection' => 'AI-system connection', 'unsure' => 'Saya tidak pasti']],
            ['ai_usage', 'Anggaran penggunaan yang anda jangka', 'single_choice', false, ['project_type' => ['ai-chatbot-automation']], ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'not-sure' => 'Not Sure']],
            ['upgrade_url', 'Masukkan URL website atau sistem sedia ada', 'url', false, ['project_type' => ['upgrade-existing']], []],
            ['upgrade_scope', 'Apa yang anda mahu ubah atau baiki?', 'multiple_choice', false, ['project_type' => ['upgrade-existing']], ['redesign' => 'Redesign', 'new-functions' => 'Tambah fungsi', 'problem-fix' => 'Fix masalah', 'payment' => 'Payment', 'login' => 'Login', 'e-commerce' => 'E-Commerce', 'ai' => 'AI', 'performance' => 'Performance', 'other' => 'Lain-lain']],
            ['design_style', 'Gaya visual yang anda suka?', 'single_choice', false, null, ['modern' => 'Modern', 'corporate' => 'Corporate', 'minimal' => 'Minimal', 'premium' => 'Premium', 'creative' => 'Creative', 'technology' => 'Technology', 'recommend' => 'Cadangkan untuk saya']],
            ['colour_preference', 'Pilih warna utama yang anda suka untuk projek ini.', 'single_choice', false, null, ['biru' => 'Biru', 'hijau' => 'Hijau', 'ungu' => 'Ungu', 'merah' => 'Merah', 'oren' => 'Oren', 'kelabu' => 'Kelabu', 'coklat' => 'Coklat', 'lain-lain' => 'Lain-lain']],
            ['colour_custom', 'Warna tersuai', 'text', false, ['colour_preference' => ['lain-lain']], []],
            ['content_logo', 'Status logo', 'single_choice', false, null, ['available' => 'Sudah ada logo', 'new-logo' => 'Mahu logo baru', 'upgrade-logo' => 'Upgrade logo sedia ada', 'unsure' => 'Tidak pasti']],
            ['content_images', 'Gambar untuk projek anda', 'multiple_choice', false, null, ['own-photos' => 'Saya ada gambar sendiri', 'stock-photos' => 'Gunakan gambar stok profesional', 'product-photos' => 'Gambar produk', 'location-photos' => 'Gambar pejabat / lokasi', 'people-photos' => 'Gambar manusia sebenar', 'recommend' => 'Cadangkan untuk saya']],
            ['content_text', 'Status teks atau kandungan', 'single_choice', false, null, ['available' => 'Sudah ada', 'not-ready' => 'Belum ada', 'need-help' => 'Perlukan bantuan']],
            ['content_domain', 'Status domain', 'single_choice', false, null, ['have-domain' => 'Sudah ada domain', 'no-domain' => 'Belum ada domain', 'suggest-domain' => 'Perlukan cadangan domain', 'unsure' => 'Tidak pasti']],
            ['domain_name', 'Nama domain sedia ada', 'text', false, ['content_domain' => ['have-domain']], []],
            ['content_hosting', 'Status hosting', 'single_choice', false, null, ['available' => 'Sudah ada', 'not-ready' => 'Belum ada', 'need-help' => 'Perlukan bantuan']],
            ['reference_available', 'Adakah anda mempunyai website atau design rujukan?', 'single_choice', false, null, ['yes' => 'Ya, saya ada', 'no' => 'Tidak', 'recommend' => 'Cadangkan untuk saya']],
            ['reference_website', 'Website rujukan (jika ada)', 'url', false, ['reference_available' => ['yes']], []],
            ['reference_likes', 'Apa yang anda suka pada rujukan tersebut?', 'textarea', false, ['reference_available' => ['yes']], []],
            ['budget', 'Julat bajet anda', 'single_choice', false, null, ['under-1000' => 'Bawah RM1,000', '1000-3000' => 'RM1,000 – RM3,000', '3000-5000' => 'RM3,000 – RM5,000', '5000-10000' => 'RM5,000 – RM10,000', 'over-10000' => 'RM10,000+', 'unsure' => 'Saya tidak pasti']],
            ['summary', 'Apa hasil yang anda mahu capai?', 'textarea', false, null, []],
            ['additional_customer_requirement', 'Ada perkara lain yang kami perlu tahu?', 'textarea', false, null, []],
        ];

        foreach ($questions as $order => [$code, $label, $type, $required, $condition, $options]) {
            $question = BuilderQuestion::query()->updateOrCreate(['code' => $code], [
                'label' => $label, 'question_type' => $type, 'is_required' => $required,
                'display_order' => $order + 1, 'condition' => $condition,
                'internal_mapping' => ['additional_customer_requirement' => $code === 'additional_customer_requirement'],
                // julat bajet diganti kos sebenar (pakej + add-on); soalan susulan jenis projek dibuang —
                // langkah "Model" kekal ringkas (kategori + pakej sahaja), add-on ditawarkan ikut pakej dipilih.
                'is_active' => ! in_array($code, [
                    'budget', 'website_purpose', 'website_functions', 'website_information', 'customer_login_method',
                    'ecommerce_product_quantity', 'ecommerce_functions', 'business_system_users', 'business_system_actions',
                    'ai_automation_solution', 'ai_usage', 'upgrade_url', 'upgrade_scope',
                ], true),
            ]);

            // Buang pilihan lama yang tiada lagi dalam konfigurasi (jawapan lama kekal dalam snapshot spesifikasi).
            BuilderQuestionOption::query()->where('builder_question_id', $question->id)->whereNotIn('code', array_keys($options) ?: ['__none__'])->delete();

            $optionPosition = 0;
            foreach ($options as $optionCode => $optionLabel) {
                BuilderQuestionOption::query()->updateOrCreate(['builder_question_id' => $question->id, 'code' => $optionCode], ['label' => $optionLabel, 'display_order' => ++$optionPosition]);
            }
        }
    }
}
