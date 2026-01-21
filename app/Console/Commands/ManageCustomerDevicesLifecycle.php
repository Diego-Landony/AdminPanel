<?php

namespace App\Console\Commands;

use App\Models\CustomerDevice;
use Illuminate\Console\Command;

class ManageCustomerDevicesLifecycle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'devices:manage-lifecycle {--dry-run : Ejecutar en modo simulación sin cambios reales}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gestiona el ciclo de vida de dispositivos: marca inactivos (365+ días) y elimina antiguos (548+ días)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('🔄 Iniciando gestión del ciclo de vida de dispositivos...');
        $this->newLine();

        // Paso 1: Marcar dispositivos como inactivos (365+ días sin uso)
        $this->info('📝 Paso 1: Marcando dispositivos inactivos (365+ días sin uso)...');
        $devicesToInactivate = CustomerDevice::shouldBeInactive()->get();
        $inactivatedCount = $devicesToInactivate->count();

        if ($dryRun) {
            $this->warn("   [DRY RUN] Se marcarían {$inactivatedCount} dispositivos como inactivos");
        } else {
            foreach ($devicesToInactivate as $device) {
                $device->markAsInactive();
            }
            $this->info("   ✓ {$inactivatedCount} dispositivos marcados como inactivos");
        }

        // Paso 2: Eliminar dispositivos antiguos (548+ días sin uso) usando soft delete
        $this->newLine();
        $this->info('🗑️  Paso 2: Eliminando dispositivos antiguos (548+ días sin uso)...');
        $devicesToDelete = CustomerDevice::shouldBeDeleted()->get();
        $deletedCount = $devicesToDelete->count();

        if ($dryRun) {
            $this->warn("   [DRY RUN] Se eliminarían {$deletedCount} dispositivos antiguos");
        } else {
            foreach ($devicesToDelete as $device) {
                $device->delete(); // Soft delete para preservar datos
            }
            $this->info("   ✓ {$deletedCount} dispositivos eliminados (soft delete)");
        }

        // Resumen
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 Resumen:');
        $this->info("   • Dispositivos marcados como inactivos: {$inactivatedCount}");
        $this->info("   • Dispositivos eliminados: {$deletedCount}");
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if ($dryRun) {
            $this->newLine();
            $this->comment('💡 Esto fue una simulación. Ejecuta sin --dry-run para aplicar cambios.');
        }

        return Command::SUCCESS;
    }
}
