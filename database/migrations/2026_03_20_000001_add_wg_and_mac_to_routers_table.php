<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->string('wg_public_key')->nullable()->after('vpn_ip');
            $table->string('mac_address')->nullable()->after('wg_public_key');
            $table->integer('api_port')->nullable()->default(80)->after('mac_address');
            $table->string('api_username')->nullable()->after('api_port');
            $table->string('api_password')->nullable()->after('api_username');
        });
    }

    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn(['wg_public_key', 'mac_address', 'api_port', 'api_username', 'api_password']);
        });
    }
};
