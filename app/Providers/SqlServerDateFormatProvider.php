<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SqlServerDateFormatProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configurar Carbon para manejar fechas de SQL Server
        $this->configureCarbonForSqlServer();
    }

    /**
     * Configurar Carbon para manejar el formato de fechas de SQL Server
     */
    private function configureCarbonForSqlServer(): void
    {
        // Agregar formato personalizado para fechas de SQL Server
        Carbon::macro('parseSqlServerDate', function ($date) {
            if (empty($date)) {
                return null;
            }

            // Limpiar el formato de SQL Server
            $date = str_replace(':AM', ' AM', $date);
            $date = str_replace(':PM', ' PM', $date);
            
            try {
                return Carbon::parse($date);
            } catch (\Exception $e) {
                // Si falla el parseo, intentar con formato específico de SQL Server
                try {
                    return Carbon::createFromFormat('M d Y h:i:s:A', $date);
                } catch (\Exception $e2) {
                    // Si todo falla, devolver la fecha original
                    return $date;
                }
            }
        });
    }
}