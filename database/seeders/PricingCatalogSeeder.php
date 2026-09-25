<?php

namespace Database\Seeders;

use App\Engines\Pricing\Models\Addon;
use App\Engines\Pricing\Models\PackageAddon;
use App\Engines\Pricing\Models\Service;
use App\Engines\Pricing\Models\ServicePackage;
use Illuminate\Database\Seeder;

class PricingCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            ['website-development', 'Website Development', 'Website yang jelas, profesional dan sesuai dengan keperluan perniagaan.', [
                ['landing-page', 'Landing Page', 1500, 'RM1,500', '2–4 hari', 'fixed', 'Single landing page, responsive/mobile, CTA WhatsApp/contact, basic enquiry form, basic SEO, deployment; 1 revision dan 7 hari complimentary support.'],
                ['starter-website', 'Starter Website', 2500, 'RM2,500', '3–5 hari', 'fixed', 'Maklumat syarikat, servis, contact, WhatsApp, Maps, enquiry form, responsive dan basic SEO; 1 revision dan 14 hari support.'],
                ['business-website', 'Business Website', 3900, 'RM3,900', '5–10 hari', 'fixed', 'Company profile, services, portfolio/gallery, testimonials, FAQ, contact/enquiry, WhatsApp, Maps, basic SEO dan relevant content management; 2 revisions dan 14 hari support.'],
                ['corporate-website', 'Corporate Website', 6900, 'RM6,900', '7–14 hari', 'fixed', 'Struktur lebih besar dengan services/divisions, projects/portfolio, management/team, blog/news, careers, advanced enquiry dan content management; 3 revisions dan 30 hari support.'],
                ['custom-website', 'Custom Website', 8000, 'RM8,000+', 'Quotation', 'starting', 'Custom review dan quotation berdasarkan Master Specification yang diluluskan.'],
            ]],
            ['e-commerce', 'E-Commerce', 'Kedai dalam talian dan katalog produk untuk operasi jualan digital.', [
                ['product-catalogue', 'Product Catalogue', 4500, 'RM4,500', '5–10 hari', 'fixed', 'Product/category/detail/search/filter, WhatsApp/enquiry dan admin product management; online checkout tidak diperlukan.'],
                ['e-commerce-starter', 'E-Commerce Starter', 6900, 'RM6,900', '7–14 hari', 'fixed', 'Cart, checkout, online payment, basic shipping, order management/notification, serta product dan order management.'],
                ['e-commerce-business', 'E-Commerce Business', 9900, 'RM9,900', '10–20 hari', 'fixed', 'Customer account/order history, variants, stock management, coupon/promotion, enhanced shipping dan sales/order reporting.'],
                ['custom-e-commerce', 'Custom E-Commerce', 15000, 'RM15,000+', 'Quotation', 'starting', 'Untuk inventory kompleks, multi-vendor, wholesale/dealer, commission, ERP atau requirement kompleks yang setara.'],
            ]],
            ['custom-web-applications', 'Custom Web Applications', 'Sistem web dibina mengikut cara pasukan dan operasi anda bekerja.', [
                ['simple-web-system', 'Simple Web System', 8000, 'RM8,000+', '7–14 hari', 'starting', 'Skop modul, pengguna/role, workflow dan data dimuktamadkan dalam Master Specification.'],
                ['business-web-app', 'Business Web App', 15000, 'RM15,000+', '2–4 minggu', 'starting', 'Skop sistem perniagaan ditentukan melalui modul, workflow, data, reporting, integration dan automation yang diluluskan.'],
                ['advanced-web-app', 'Advanced Web App', 25000, 'RM25,000+', '4–8+ minggu', 'starting', 'Untuk skop aplikasi lanjutan dengan keperluan teknikal dan operasi yang diperincikan melalui Master Specification.'],
                ['enterprise-complex', 'Enterprise / Complex', null, 'Quotation', 'Manual review', 'quote', 'Memerlukan requirement review dan quotation manual.'],
            ]],
            ['ai-automation', 'AI & Automation', 'Automasi dan pembantu AI untuk proses kerja serta komunikasi perniagaan.', [
                ['telegram-business-bot', 'Telegram Business Bot', 1500, 'RM1,500+', null, 'starting', 'Bot Telegram berdasarkan aliran komunikasi atau automasi yang diluluskan.'],
                ['ai-website-chatbot', 'AI Website Chatbot', 2500, 'RM2,500+', null, 'starting', 'Chatbot website untuk soalan pelanggan berdasarkan kandungan dan skop yang diluluskan.'],
                ['ai-knowledge-bot', 'AI Knowledge Bot', 4500, 'RM4,500+', null, 'starting', 'Pembantu AI berasaskan pengetahuan syarikat yang diluluskan.'],
                ['workflow-automation', 'Workflow Automation', 2500, 'RM2,500+', null, 'starting', 'Automasi aliran kerja yang dinyatakan dan diluluskan dalam Master Specification.'],
                ['custom-ai-business-assistant', 'Custom AI Business Assistant', 5000, 'RM5,000+', null, 'starting', 'Pembantu AI disesuaikan kepada proses perniagaan yang telah disemak.'],
                ['advanced-ai-solution', 'Advanced AI Solution', null, 'Quotation', 'Manual review', 'quote', 'Memerlukan review manual untuk skop AI lanjutan.'],
            ]],
            ['system-api-integration', 'System/API Integration', 'Sambungkan sistem, API dan saluran komunikasi yang digunakan perniagaan anda.', [
                ['basic-api-integration', 'Basic API Integration', 2500, 'RM2,500+', null, 'starting', 'Integrasi 1 API/sistem dengan authentication, basic data exchange dan basic error handling mengikut skop integrasi yang ditetapkan.'],
                ['business-integration', 'Business Integration', 5000, 'RM5,000+', null, 'starting', 'Integrasi sehingga 3 sistem/API dengan data mapping, workflow integration, authentication, error handling dan basic monitoring.'],
                ['advanced-integration', 'Advanced Integration', 10000, 'RM10,000+', null, 'starting', 'Integrasi berbilang sistem dengan complex API, webhook, automation, advanced data flow, error handling, integration logic dan monitoring.'],
                ['custom-integration', 'Custom Integration', null, 'Quotation', 'Manual review', 'quote', 'Requirement review, integration architecture, custom data flow dan custom API logic melalui Master Specification.'],
            ]],
            ['maintenance-support', 'Maintenance & Support', 'Sokongan berterusan untuk memastikan aset digital kekal terurus.', [
                ['basic-care', 'Basic Care', 199, 'RM199 / bulan', null, 'fixed', 'Maintenance berterusan mengikut terma pelan; bukan unlimited development.'],
                ['business-care', 'Business Care', 399, 'RM399 / bulan', null, 'fixed', 'Maintenance berterusan mengikut terma pelan; bukan unlimited development.'],
                ['e-commerce-care', 'E-Commerce Care', 699, 'RM699 / bulan', null, 'fixed', 'Maintenance berterusan mengikut terma pelan; bukan unlimited development.'],
                ['web-app-care', 'Web App Care', 999, 'RM999+ / bulan', null, 'starting', 'Maintenance aplikasi web mengikut terma pelan dan skop yang dipersetujui.'],
                ['custom-sla', 'Custom SLA', null, 'Quotation', 'Manual review', 'quote', 'Custom service level agreement melalui quotation.'],
            ]],
        ];

        foreach ($catalog as $serviceOrder => [$slug, $name, $summary, $packages]) {
            $service = Service::query()->updateOrCreate(['slug' => $slug], ['name' => $name, 'summary' => $summary, 'display_order' => $serviceOrder + 1, 'is_active' => true]);
            foreach ($packages as $packageOrder => [$packageSlug, $packageName, $amount, $label, $delivery, $type, $packageSummary]) {
                ServicePackage::query()->updateOrCreate(['slug' => $packageSlug], ['service_id' => $service->id, 'name' => $packageName, 'summary' => $packageSummary, 'inclusions' => $this->inclusions()[$packageSlug] ?? null, 'price_type' => $type, 'price_amount' => $amount, 'price_label' => $label, 'delivery_estimate' => $delivery, 'display_order' => $packageOrder + 1, 'is_active' => true]);
            }
        }

        ServicePackage::query()->whereIn('slug', ['website-chatbot', 'knowledge-bot', 'ai-assistant', 'advanced-ai-automation', 'business-system-integration', 'complex-integration', 'basic-api', 'payment-gateway', 'email-integration', 'telegram-integration', 'whatsapp-integration', 'external-business-system', 'complex-multiple-integration', 'maintenance-basic', 'maintenance-business', 'maintenance-ecommerce', 'maintenance-web-app'])->update(['is_active' => false]);

        // Identiti add-on + harga standard (Shared Add-on Catalogue). Kolum terakhir: kedudukan dalam katalog (null = tidak dipaparkan).
        $addons = [
            ['additional-page', 'Additional simple page', 350, 'RM350/page', 'fixed', 1],
            ['additional-service-page', 'Additional service / landing page', 500, 'RM500–RM800/page', 'starting', 2],
            ['additional-corporate-page', 'Additional corporate page', 500, 'RM500/page', 'fixed', 3],
            ['copywriting', 'Copywriting', 300, 'RM300–RM500/page', 'starting', 4],
            ['content-migration', 'Content migration', 100, 'RM100/page', 'fixed', 5],
            ['blog-news', 'Blog / News', 500, 'RM500–RM800', 'starting', 6],
            ['portfolio-module', 'Gallery', 500, 'RM500', 'fixed', 7],
            ['trust-section', 'Testimonials / FAQ / Trust section', 500, 'RM500–RM600', 'starting', 8],
            ['custom-form', 'Custom enquiry form', 600, 'RM600+', 'starting', 9],
            ['google-maps', 'Google Maps', 150, 'RM150', 'fixed', 10],
            ['site-search', 'Site Search', 500, 'RM500+', 'starting', 11],
            ['additional-language', 'Additional language', 400, 'RM400–RM1,500/language', 'starting', 12],
            ['appointment-booking', 'Booking / Appointment', 1800, 'RM1,800+', 'starting', 13],
            ['membership', 'Membership', 2300, 'RM2,300+', 'starting', 14],
            ['customer-portal', 'Customer Portal', 3000, 'RM3,000+', 'starting', 15],
            ['online-payment-integration', 'Payment Gateway', 1800, 'RM1,800+', 'starting', 16],
            ['shipping-integration', 'Shipping Integration', 1500, 'RM1,500+', 'starting', 17],
            ['stock-management', 'Stock Management', 1500, 'RM1,500+', 'starting', 18],
            ['coupon-promotion', 'Coupon / Promotion', 1000, 'RM1,000', 'fixed', 19],
            ['marketplace-integration', 'Marketplace Integration', 3000, 'RM3,000+/platform', 'starting', 20],
            ['external-api-integration', 'API Integration', 2500, 'RM2,500–RM5,000+', 'starting', 21],
            ['advanced-automation', 'Advanced Automation', 2500, 'RM2,500+', 'starting', 22],
            ['basic-seo-setup', 'Basic SEO Setup', 800, 'RM800–RM1,200', 'starting', 23],
            ['advanced-seo-setup', 'Advanced SEO', 1500, 'RM1,500–RM2,500+', 'starting', 24],
            ['analytics-setup', 'Analytics / Tracking', 300, 'RM300–RM1,000+', 'starting', 25],
            ['speed-optimisation', 'Speed Optimisation', 300, 'RM300/page atau quotation', 'starting', 26],
            ['security-hardening', 'Security Hardening', 2000, 'RM2,000+', 'starting', 27],
            ['maintenance-plan', 'Maintenance', 200, 'RM200–RM500/month', 'monthly', 28],
            ['advanced-form', 'Advanced form', 500, 'RM500+', 'starting', null],
            ['additional-revision', 'Additional revision', 300, 'RM300/round', 'fixed', null],
            ['advanced-cms', 'Advanced CMS', 2000, 'RM2,000+', 'starting', null],
            ['advanced-search', 'Advanced search', 2000, 'RM2,000+', 'starting', null],
            ['additional-module', 'Additional module', 1500, 'RM1,500+', 'starting', null],
            ['additional-product', 'Additional product setup', 20, 'RM20/product', 'fixed', null],
            ['product-variants', 'Product variants', 800, 'RM800+', 'starting', null],
            ['online-checkout', 'Online checkout', 1500, 'RM1,500+', 'starting', null],
            ['additional-payment-gateway', 'Additional payment gateway', 800, 'RM800/gateway', 'fixed', null],
            ['advanced-shipping', 'Advanced shipping', 1500, 'RM1,500+', 'starting', null],
            ['customer-login', 'Customer account', 1500, 'RM1,500', 'fixed', null],
            ['advanced-reporting', 'Advanced reporting', 1500, 'RM1,500+', 'starting', null],
            ['loyalty-rewards', 'Loyalty / rewards', 2500, 'RM2,500+', 'starting', null],
            ['subscription', 'Subscription', 3000, 'RM3,000+', 'starting', null],
            ['wholesale-dealer', 'Wholesale / dealer', 4000, 'RM4,000+', 'starting', null],
            ['erp-integration', 'ERP integration', 6000, 'RM6,000+', 'starting', null],
            ['multi-vendor', 'Multi-vendor', 8000, 'RM8,000+', 'starting', null],
            ['advanced-inventory', 'Advanced inventory', 5000, 'RM5,000+', 'starting', null],
            ['dealer-portal', 'Dealer portal', 5000, 'RM5,000+', 'starting', null],
            ['commission-engine', 'Commission engine', 5000, 'RM5,000+', 'starting', null],
            ['notification', 'Notification', 800, 'RM800+', 'starting', null],
            ['custom-dashboard', 'Custom dashboard', 1500, 'RM1,500+', 'starting', null],
            ['mobile-app', 'Mobile app', 10000, 'RM10,000+', 'starting', null],
            ['advanced-permission', 'Advanced permission', 1500, 'RM1,500+', 'starting', null],
            ['ai-module', 'AI module', 5000, 'RM5,000+', 'starting', null],
            ['multi-tenant', 'Multi-tenant', 8000, 'RM8,000+', 'starting', null],
            ['advanced-api', 'Advanced API', 5000, 'RM5,000+', 'starting', null],
            ['enterprise-integration', 'Enterprise integration', 6000, 'RM6,000+', 'starting', null],
            ['enterprise-module', 'Enterprise module', null, 'Quotation', 'quote', null],
            ['crm-erp-integration', 'ERP / CRM', 5000, 'RM5,000+', 'starting', null],
            ['multi-system-integration', 'Multi-system integration', null, 'Quotation', 'quote', null],
            ['infrastructure-architecture', 'Infrastructure architecture', null, 'Quotation', 'quote', null],
            ['additional-flow', 'Additional flow', 500, 'RM500/flow', 'fixed', null],
            ['database-integration', 'Database integration', 1500, 'RM1,500+', 'starting', null],
            ['ai-integration', 'AI integration', 2500, 'RM2,500+', 'starting', null],
            ['knowledge-base', 'Knowledge base', 1500, 'RM1,500+', 'starting', null],
            ['crm-integration', 'CRM integration', 2500, 'RM2,500+', 'starting', null],
            ['whatsapp-integration', 'WhatsApp integration', 2500, 'RM2,500+', 'starting', null],
            ['advanced-ai-workflow', 'Advanced AI workflow', 3000, 'RM3,000+', 'starting', null],
            ['additional-knowledge-source', 'Additional knowledge source', 1000, 'RM1,000+', 'starting', null],
            ['advanced-rag', 'Advanced RAG', 3000, 'RM3,000+', 'starting', null],
            ['user-access-control', 'User access control', 1500, 'RM1,500+', 'starting', null],
            ['additional-workflow', 'Additional workflow', 1000, 'RM1,000/workflow', 'fixed', null],
            ['additional-api', 'Additional API', 1500, 'RM1,500+', 'starting', null],
            ['ai-processing', 'AI processing', 2500, 'RM2,500+', 'starting', null],
            ['multi-system-workflow', 'Multi-system workflow', 3500, 'RM3,500+', 'starting', null],
            ['voice-ai', 'Voice AI', 3500, 'RM3,500+', 'starting', null],
            ['ai-automation', 'AI automation', 3500, 'RM3,500+', 'starting', null],
            ['ai-agent', 'AI agent', null, 'Quotation', 'quote', null],
            ['multi-agent-system', 'Multi-agent system', null, 'Quotation', 'quote', null],
            ['custom-ai-infrastructure', 'Custom AI infrastructure', null, 'Quotation', 'quote', null],
            ['webhook', 'Webhook', 1000, 'RM1,000+', 'starting', null],
            ['advanced-data-mapping', 'Advanced data mapping', 1500, 'RM1,500+', 'starting', null],
            ['custom-authentication', 'Custom authentication', 1000, 'RM1,000+', 'starting', null],
            ['additional-system', 'Additional system', 2500, 'RM2,500+', 'starting', null],
            ['realtime-sync', 'Real-time synchronisation', 3000, 'RM3,000+', 'starting', null],
            ['advanced-monitoring', 'Advanced monitoring', 2000, 'RM2,000+', 'starting', null],
            ['advanced-middleware', 'Advanced middleware', 5000, 'RM5,000+', 'starting', null],
            ['complex-middleware', 'Complex middleware', null, 'Quotation', 'quote', null],
            ['multi-system-architecture', 'Multi-system architecture', null, 'Quotation', 'quote', null],
            ['custom-automation', 'Custom automation', null, 'Quotation', 'quote', null],
            ['basic-chatbot', 'Basic Chatbot', 1500, 'RM1,500+', 'starting', null],
        ];

        foreach ($addons as $order => [$slug, $name, $amount, $label, $type, $catalogueOrder]) {
            Addon::query()->updateOrCreate(['slug' => $slug], ['name' => $name, 'summary' => null, 'price_type' => $type, 'price_amount' => $amount, 'price_label' => $label, 'display_order' => $order + 1, 'catalogue_order' => $catalogueOrder, 'is_active' => true]);
        }

        Addon::query()->whereIn('slug', ['domain-setup', 'hosting-setup', 'email-setup', 'extra-page', 'payment-gateway-integration', 'product-upload'])->update(['is_active' => false]);

        $this->seedPackageAddons();
    }

    /** Add-on ikut pakej: [slug add-on, nama paparan, harga, label, jenis harga]. */
    private function packageAddons(): array
    {
        return [
            'landing-page' => [
                ['additional-page', 'Additional page', 350, 'RM350/page', 'fixed'], ['advanced-form', 'Advanced form', 500, 'RM500+', 'starting'],
                ['copywriting', 'Copywriting', 300, 'RM300/page', 'fixed'], ['additional-revision', 'Additional revision', 300, 'RM300/round', 'fixed'],
                ['advanced-seo-setup', 'Advanced SEO', 1000, 'RM1,000+', 'starting'], ['analytics-setup', 'Analytics setup', 300, 'RM300', 'fixed'],
                ['maintenance-plan', 'Maintenance', 200, 'RM200/month', 'monthly'],
            ],
            'starter-website' => [
                ['additional-page', 'Additional page', 350, 'RM350/page', 'fixed'], ['blog-news', 'Blog / News', 500, 'RM500', 'fixed'],
                ['portfolio-module', 'Gallery', 500, 'RM500', 'fixed'], ['custom-form', 'Custom form', 600, 'RM600+', 'starting'],
                ['copywriting', 'Copywriting', 300, 'RM300/page', 'fixed'], ['additional-language', 'Additional language', 400, 'RM400/page', 'fixed'],
                ['advanced-seo-setup', 'Advanced SEO', 1200, 'RM1,200+', 'starting'], ['analytics-setup', 'Analytics', 300, 'RM300', 'fixed'],
                ['maintenance-plan', 'Maintenance', 250, 'RM250/month', 'monthly'],
            ],
            'business-website' => [
                ['additional-page', 'Additional page', 400, 'RM400/page', 'fixed'], ['blog-news', 'Blog / News', 800, 'RM800', 'fixed'],
                ['appointment-booking', 'Booking', 1800, 'RM1,800+', 'starting'], ['membership', 'Membership', 2300, 'RM2,300+', 'starting'],
                ['advanced-cms', 'Advanced CMS', 2000, 'RM2,000+', 'starting'], ['copywriting', 'Copywriting', 350, 'RM350/page', 'fixed'],
                ['additional-language', 'Additional language', 400, 'RM400/page', 'fixed'], ['advanced-seo-setup', 'Advanced SEO', 1500, 'RM1,500+', 'starting'],
                ['analytics-setup', 'Analytics', 500, 'RM500', 'fixed'], ['maintenance-plan', 'Maintenance', 350, 'RM350/month', 'monthly'],
            ],
            'corporate-website' => [
                ['additional-page', 'Additional page', 500, 'RM500/page', 'fixed'], ['additional-language', 'Additional language', 1200, 'RM1,200/language', 'fixed'],
                ['appointment-booking', 'Booking', 2500, 'RM2,500+', 'starting'], ['membership', 'Membership / portal', 4000, 'RM4,000+', 'starting'],
                ['advanced-search', 'Advanced search', 2000, 'RM2,000+', 'starting'], ['external-api-integration', 'API integration', 3000, 'RM3,000+', 'starting'],
                ['advanced-seo-setup', 'Advanced SEO', 2500, 'RM2,500+', 'starting'], ['analytics-setup', 'Analytics / dashboard', 1000, 'RM1,000+', 'starting'],
                ['maintenance-plan', 'Maintenance', 500, 'RM500/month', 'monthly'],
            ],
            'custom-website' => [
                ['additional-module', 'Additional module', 1500, 'RM1,500+', 'starting'], ['additional-page', 'Additional page', 500, 'RM500+', 'starting'],
                ['external-api-integration', 'API integration', 3000, 'RM3,000+', 'starting'], ['advanced-automation', 'Automation', 2500, 'RM2,500+', 'starting'],
                ['advanced-seo-setup', 'Advanced SEO', 2500, 'RM2,500+', 'starting'], ['maintenance-plan', 'Maintenance', 500, 'RM500+/month', 'monthly'],
            ],
            'product-catalogue' => [
                ['additional-page', 'Additional page', 400, 'RM400/page', 'fixed'], ['additional-product', 'Additional product setup', 20, 'RM20/product', 'fixed'],
                ['product-variants', 'Product variants', 800, 'RM800+', 'starting'], ['online-checkout', 'Online checkout', 1500, 'RM1,500+', 'starting'],
                ['online-payment-integration', 'Payment gateway', 1800, 'RM1,800+', 'starting'], ['shipping-integration', 'Shipping integration', 1500, 'RM1,500+', 'starting'],
                ['stock-management', 'Stock management', 1500, 'RM1,500+', 'starting'],
            ],
            'e-commerce-starter' => [
                ['additional-page', 'Additional page', 400, 'RM400/page', 'fixed'], ['additional-product', 'Additional product', 20, 'RM20/product', 'fixed'],
                ['additional-payment-gateway', 'Additional payment gateway', 800, 'RM800/gateway', 'fixed'], ['advanced-shipping', 'Advanced shipping', 1500, 'RM1,500+', 'starting'],
                ['stock-management', 'Stock management', 1500, 'RM1,500+', 'starting'], ['coupon-promotion', 'Coupon / promotion', 1000, 'RM1,000', 'fixed'],
                ['customer-login', 'Customer account', 1500, 'RM1,500', 'fixed'], ['advanced-reporting', 'Advanced reporting', 1500, 'RM1,500+', 'starting'],
            ],
            'e-commerce-business' => [
                ['additional-page', 'Additional page', 450, 'RM450/page', 'fixed'], ['additional-product', 'Additional product', 20, 'RM20/product', 'fixed'],
                ['loyalty-rewards', 'Loyalty / rewards', 2500, 'RM2,500+', 'starting'], ['subscription', 'Subscription', 3000, 'RM3,000+', 'starting'],
                ['wholesale-dealer', 'Wholesale / dealer', 4000, 'RM4,000+', 'starting'], ['marketplace-integration', 'Marketplace integration', 3000, 'RM3,000+/platform', 'starting'],
                ['erp-integration', 'ERP integration', 6000, 'RM6,000+', 'starting'],
            ],
            'custom-e-commerce' => [
                ['multi-vendor', 'Multi-vendor', 8000, 'RM8,000+', 'starting'], ['advanced-inventory', 'Advanced inventory', 5000, 'RM5,000+', 'starting'],
                ['erp-integration', 'ERP integration', 6000, 'RM6,000+', 'starting'], ['dealer-portal', 'Dealer portal', 5000, 'RM5,000+', 'starting'],
                ['commission-engine', 'Commission engine', 5000, 'RM5,000+', 'starting'], ['marketplace-integration', 'Marketplace integration', 4000, 'RM4,000+/platform', 'starting'],
            ],
            'simple-web-system' => [
                ['additional-module', 'Additional module', 1500, 'RM1,500+', 'starting'], ['advanced-reporting', 'Advanced report', 1500, 'RM1,500+', 'starting'],
                ['external-api-integration', 'API integration', 2500, 'RM2,500+', 'starting'], ['advanced-automation', 'Automation', 2500, 'RM2,500+', 'starting'],
                ['notification', 'Notification', 800, 'RM800+', 'starting'], ['custom-dashboard', 'Custom dashboard', 1500, 'RM1,500+', 'starting'],
            ],
            'business-web-app' => [
                ['additional-module', 'Additional module', 2000, 'RM2,000+', 'starting'], ['advanced-reporting', 'Advanced reporting', 2000, 'RM2,000+', 'starting'],
                ['external-api-integration', 'External API', 3000, 'RM3,000+', 'starting'], ['advanced-automation', 'Advanced automation', 3000, 'RM3,000+', 'starting'],
                ['mobile-app', 'Mobile app', 10000, 'RM10,000+', 'starting'], ['advanced-permission', 'Advanced permission', 1500, 'RM1,500+', 'starting'],
            ],
            'advanced-web-app' => [
                ['ai-module', 'AI module', 5000, 'RM5,000+', 'starting'], ['mobile-app', 'Mobile application', 10000, 'RM10,000+', 'starting'],
                ['multi-tenant', 'Multi-tenant', 8000, 'RM8,000+', 'starting'], ['advanced-api', 'Advanced API', 5000, 'RM5,000+', 'starting'],
                ['enterprise-integration', 'Enterprise integration', 6000, 'RM6,000+', 'starting'],
            ],
            'enterprise-complex' => [
                ['enterprise-module', 'Enterprise module', null, 'Quotation', 'quote'], ['crm-erp-integration', 'ERP / CRM', null, 'Quotation', 'quote'],
                ['multi-system-integration', 'Multi-system integration', null, 'Quotation', 'quote'], ['advanced-automation', 'Advanced automation', null, 'Quotation', 'quote'],
                ['infrastructure-architecture', 'Infrastructure architecture', null, 'Quotation', 'quote'],
            ],
            'telegram-business-bot' => [
                ['additional-flow', 'Additional flow', 500, 'RM500/flow', 'fixed'], ['database-integration', 'Database integration', 1500, 'RM1,500+', 'starting'],
                ['online-payment-integration', 'Payment integration', 2000, 'RM2,000+', 'starting'], ['external-api-integration', 'External API', 2500, 'RM2,500+', 'starting'],
                ['ai-integration', 'AI integration', 2500, 'RM2,500+', 'starting'],
            ],
            'ai-website-chatbot' => [
                ['knowledge-base', 'Knowledge base', 1500, 'RM1,500+', 'starting'], ['crm-integration', 'CRM integration', 2500, 'RM2,500+', 'starting'],
                ['whatsapp-integration', 'WhatsApp integration', 2500, 'RM2,500+', 'starting'], ['advanced-ai-workflow', 'Advanced AI workflow', 3000, 'RM3,000+', 'starting'],
                ['analytics-setup', 'Analytics', 1500, 'RM1,500+', 'starting'],
            ],
            'ai-knowledge-bot' => [
                ['additional-knowledge-source', 'Additional knowledge source', 1000, 'RM1,000+', 'starting'], ['advanced-rag', 'Advanced RAG', 3000, 'RM3,000+', 'starting'],
                ['crm-erp-integration', 'CRM / ERP', 5000, 'RM5,000+', 'starting'], ['user-access-control', 'User access control', 1500, 'RM1,500+', 'starting'],
                ['analytics-setup', 'Analytics', 1500, 'RM1,500+', 'starting'],
            ],
            'workflow-automation' => [
                ['additional-workflow', 'Additional workflow', 1000, 'RM1,000/workflow', 'fixed'], ['additional-api', 'Additional API', 1500, 'RM1,500+', 'starting'],
                ['ai-processing', 'AI processing', 2500, 'RM2,500+', 'starting'], ['multi-system-workflow', 'Multi-system workflow', 3500, 'RM3,500+', 'starting'],
            ],
            'custom-ai-business-assistant' => [
                ['voice-ai', 'Voice AI', 3500, 'RM3,500+', 'starting'], ['crm-erp-integration', 'CRM / ERP', 5000, 'RM5,000+', 'starting'],
                ['whatsapp-integration', 'WhatsApp / Telegram', 2500, 'RM2,500+', 'starting'], ['advanced-rag', 'Advanced RAG', 3500, 'RM3,500+', 'starting'],
                ['ai-automation', 'AI automation', 3500, 'RM3,500+', 'starting'],
            ],
            'advanced-ai-solution' => [
                ['ai-agent', 'AI agent', null, 'Quotation', 'quote'], ['multi-agent-system', 'Multi-agent system', null, 'Quotation', 'quote'],
                ['advanced-rag', 'Advanced RAG', null, 'Quotation', 'quote'], ['enterprise-integration', 'Enterprise integration', null, 'Quotation', 'quote'],
                ['custom-ai-infrastructure', 'Custom AI infrastructure', null, 'Quotation', 'quote'],
            ],
            'basic-api-integration' => [
                ['additional-api', 'Additional API', 1500, 'RM1,500+', 'starting'], ['webhook', 'Webhook', 1000, 'RM1,000+', 'starting'],
                ['advanced-data-mapping', 'Advanced data mapping', 1500, 'RM1,500+', 'starting'], ['advanced-automation', 'Automation', 2500, 'RM2,500+', 'starting'],
                ['custom-authentication', 'Custom authentication', 1000, 'RM1,000+', 'starting'],
            ],
            'business-integration' => [
                ['additional-system', 'Additional system', 2500, 'RM2,500+', 'starting'], ['advanced-automation', 'Advanced automation', 3000, 'RM3,000+', 'starting'],
                ['webhook', 'Webhook', 1000, 'RM1,000+', 'starting'], ['realtime-sync', 'Real-time synchronisation', 3000, 'RM3,000+', 'starting'],
                ['advanced-monitoring', 'Advanced monitoring', 2000, 'RM2,000+', 'starting'],
            ],
            'advanced-integration' => [
                ['erp-integration', 'ERP integration', 6000, 'RM6,000+', 'starting'], ['crm-integration', 'CRM integration', 4000, 'RM4,000+', 'starting'],
                ['online-payment-integration', 'Payment gateway', 1800, 'RM1,800+', 'starting'], ['additional-system', 'Additional system', 3000, 'RM3,000+', 'starting'],
                ['advanced-middleware', 'Advanced middleware', 5000, 'RM5,000+', 'starting'],
            ],
            'custom-integration' => [
                ['enterprise-integration', 'Enterprise integration', null, 'Quotation', 'quote'], ['complex-middleware', 'Complex middleware', null, 'Quotation', 'quote'],
                ['multi-system-architecture', 'Multi-system architecture', null, 'Quotation', 'quote'], ['custom-automation', 'Custom automation', null, 'Quotation', 'quote'],
                ['advanced-monitoring', 'Advanced monitoring', null, 'Quotation', 'quote'],
            ],
        ];
    }

    private function seedPackageAddons(): void
    {
        $addonIds = Addon::query()->pluck('id', 'slug');
        foreach ($this->packageAddons() as $packageSlug => $rows) {
            $packageId = ServicePackage::query()->where('slug', $packageSlug)->value('id');
            if (! $packageId) {
                continue;
            }
            $keep = [];
            foreach ($rows as $order => [$addonSlug, $name, $amount, $label, $type]) {
                $row = PackageAddon::query()->updateOrCreate(['service_package_id' => $packageId, 'addon_id' => $addonIds[$addonSlug]], ['name' => $name, 'price_type' => $type, 'price_amount' => $amount, 'price_label' => $label, 'display_order' => $order + 1, 'is_active' => true]);
                $keep[] = $row->id;
            }
            PackageAddon::query()->where('service_package_id', $packageId)->whereNotIn('id', $keep)->update(['is_active' => false]);
        }
    }

    /** Senarai "Included dalam Pakej". */
    private function inclusions(): array
    {
        return [
            'landing-page' => ['1 page', 'Responsive desktop/tablet/mobile', 'Hero section', 'About / introduction', 'Services / benefits', 'CTA WhatsApp / Contact', 'Basic enquiry form', 'Basic on-page SEO', 'Deployment', '1 revision', '7 hari support'],
            'starter-website' => ['Up to 5 pages', 'Home', 'About / Company', 'Services', 'Contact', 'WhatsApp', 'Google Maps', 'Enquiry form', 'Responsive design', 'Basic SEO', '1 revision', '14 hari support'],
            'business-website' => ['Up to 10 pages', 'Company profile', 'Services', 'Portfolio / Gallery', 'Testimonials', 'FAQ', 'Contact / Enquiry', 'WhatsApp', 'Google Maps', 'Basic SEO', 'Basic CMS', '2 revisions', '14 hari support'],
            'corporate-website' => ['Up to 20 pages', 'Corporate profile', 'Services / divisions', 'Projects / portfolio', 'Management / team', 'Blog / News', 'Careers', 'Advanced enquiry', 'CMS', 'Responsive design', 'Basic SEO', '3 revisions', '30 hari support'],
            'custom-website' => ['Custom page structure', 'Custom UI/UX', 'Custom functionality', 'CMS jika diperlukan', 'Responsive', 'Requirement review', 'Master Specification'],
            'product-catalogue' => ['Up to 10 pages', 'Home', 'About', 'Product listing', 'Category', 'Product detail', 'Contact', 'Search / filter', 'WhatsApp / enquiry', 'Up to 50 products', 'Product management', 'Responsive'],
            'e-commerce-starter' => ['Up to 10 pages', 'Up to 100 products', 'Product categories', 'Product variants', 'Cart', 'Checkout', '1 online payment gateway', 'Basic shipping', 'Order management', 'Order notification', 'Product management', 'Responsive'],
            'e-commerce-business' => ['Up to 15 pages', 'Up to 300 products', 'Semua fungsi Starter', 'Customer account', 'Order history', 'Product variants', 'Stock management', 'Coupon / promotion', 'Enhanced shipping', 'Sales reporting', 'Order reporting'],
            'custom-e-commerce' => ['Custom storefront', 'Custom product architecture', 'Custom checkout', 'Custom order workflow', 'Custom modules', 'Requirement review', 'Master Specification'],
            'simple-web-system' => ['Up to 5 core modules', 'User login', 'Roles / permissions', 'CRUD data management', 'Basic workflow', 'Basic dashboard', 'Basic reporting', 'Responsive admin interface', 'Master Specification'],
            'business-web-app' => ['Up to 10 core modules', 'User / role management', 'Business workflow', 'Data management', 'Dashboard', 'Reporting', 'Approved integration', 'Approved automation', 'Notification', 'Activity / audit log'],
            'advanced-web-app' => ['Complex modules', 'Complex workflow', 'Advanced roles / permissions', 'Advanced reporting', 'API integration', 'Automation', 'Custom dashboard', 'Activity / audit system', 'Master Specification'],
            'enterprise-complex' => ['Requirement analysis', 'System architecture', 'Solution design', 'Master Specification', 'Custom development'],
            'telegram-business-bot' => ['1 Telegram bot', 'Up to 10 defined flows', 'Automated responses', 'Basic commands', 'Business information', 'Basic notification'],
            'ai-website-chatbot' => ['1 website chatbot', 'FAQ / customer Q&A', 'Website content knowledge', 'Basic conversation flow', 'Lead enquiry capture'],
            'ai-knowledge-bot' => ['Company knowledge base', 'Document-based knowledge', 'AI Q&A', 'Controlled knowledge scope', 'Basic administration'],
            'workflow-automation' => ['Up to 3 workflows', 'Trigger / action', 'Business process automation', 'Approved integration', 'Basic notification'],
            'custom-ai-business-assistant' => ['Custom AI assistant', 'Business knowledge', 'Defined workflow', 'Custom instructions', '1 approved integration'],
            'advanced-ai-solution' => ['Requirement review', 'AI architecture', 'Custom AI workflow', 'Technical assessment', 'Master Specification'],
            'basic-api-integration' => ['1 API / system', 'Authentication', 'Basic data exchange', 'Basic error handling', 'Defined integration scope'],
            'business-integration' => ['Up to 3 systems / APIs', 'Data mapping', 'Workflow integration', 'Authentication', 'Error handling', 'Basic monitoring'],
            'advanced-integration' => ['Multiple systems', 'Complex API integration', 'Webhook', 'Automation', 'Advanced data flow', 'Error handling', 'Integration logic', 'Monitoring'],
            'custom-integration' => ['Requirement review', 'Integration architecture', 'Custom data flow', 'Custom API logic', 'Master Specification'],
        ];
    }
}
