<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port de app.RolePermission : la matrice du §78 devenue DONNÉE. Une ligne par
 * couple rôle × permission, « Aucune » comprise — c'est ce qui distingue
 * « retiré » de « jamais paramétré ». Portée par défaut = Aucune (refus).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('permission_code', 64);
            $table->enum('portee', ['Aucune', 'Siennes', 'Equipe', 'Toutes'])->default('Aucune');

            $table->primary(['role_id', 'permission_code']);
            $table->foreign('permission_code')->references('code')->on('permissions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
