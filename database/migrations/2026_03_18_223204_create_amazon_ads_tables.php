<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Configurações Globais (Por Tenant) - LWA e Margem
        Schema::create('amazon_ads_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('profile_id')->nullable();
            $table->string('client_id')->nullable();
            $table->string('client_secret')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->string('region')->default('na'); // SA, NA, EU
            $table->decimal('margem_alvo_padrao', 5, 2)->default(20.00)->comment('Margem alvo global em porcentagem');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // 2. Configurações por SKU (Automaker)
        Schema::create('amazon_ads_sku_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('sku');
            $table->boolean('is_active')->default(false)->comment('Robo ligado/desligado para este SKU');
            $table->decimal('margem_alvo', 5, 2)->nullable()->comment('Margem customizada que sobrepoe a global');
            $table->json('keywords')->nullable()->comment('Ate 10 keywords manuais');
            $table->json('categories')->nullable()->comment('1 a 5 categorias alvo finais');
            $table->json('asins')->nullable()->comment('ASINs concorrentes (opcional)');
            $table->timestamps();
            
            $table->unique(['empresa_id', 'sku']);
        });

        // 3. Mirror das Campanhas
        Schema::create('amazon_ads_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('campaign_id_amz')->unique();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->enum('type', ['auto_general', 'auto_individual', 'manual_kw', 'manual_category', 'manual_asin']);
            $table->string('state')->default('enabled'); // enabled, paused, archived
            $table->decimal('daily_budget', 10, 2);
            $table->string('bidding_strategy')->default('legacyForSales'); // dynamic bids up and down
            $table->timestamps();
            $table->index('campaign_id_amz');
        });

        // 4. Mirror dos AdGroups
        Schema::create('amazon_ads_ad_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('amazon_ads_campaigns')->onDelete('cascade');
            $table->string('ad_group_id_amz')->unique();
            $table->string('name');
            $table->decimal('default_bid', 8, 2);
            $table->string('state')->default('enabled');
            $table->timestamps();
        });

        // 5. Mirror dos Targets / Keywords
        Schema::create('amazon_ads_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_group_id')->constrained('amazon_ads_ad_groups')->onDelete('cascade');
            $table->string('target_id_amz')->unique();
            $table->string('type'); // keyword, category, asin
            $table->string('match_type')->nullable(); // exact, phrase, broad
            $table->string('value'); // The actual string / ID
            $table->decimal('bid', 8, 2)->nullable();
            $table->string('state')->default('enabled');
            $table->timestamps();
        });

        // 6. Logs do Robo / Actions
        Schema::create('amazon_ads_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('action'); // pause_campaign, update_bid, increase_budget
            $table->string('entity_type'); // campaign, ad_group, target, sku
            $table->string('entity_id_amz')->nullable();
            $table->string('sku')->nullable();
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        // 7. Sync Metrics Diarios (Desempenho cacheado para as Regras do robo)
        Schema::create('amazon_ads_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->date('date');
            $table->string('entity_type'); // campaign, ad_group, target
            $table->string('entity_id_amz');
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->decimal('spend', 10, 2)->default(0);
            $table->decimal('sales', 10, 2)->default(0);
            $table->integer('orders')->default(0);
            $table->decimal('acos', 5, 2)->default(0);
            $table->timestamps();
            
            $table->unique(['date', 'entity_type', 'entity_id_amz'], 'metrics_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amazon_ads_metrics');
        Schema::dropIfExists('amazon_ads_logs');
        Schema::dropIfExists('amazon_ads_targets');
        Schema::dropIfExists('amazon_ads_ad_groups');
        Schema::dropIfExists('amazon_ads_campaigns');
        Schema::dropIfExists('amazon_ads_sku_configs');
        Schema::dropIfExists('amazon_ads_configs');
    }
};
