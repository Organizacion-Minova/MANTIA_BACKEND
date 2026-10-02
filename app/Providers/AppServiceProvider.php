<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\CategoryGroup;
use App\Models\Category;
use App\Models\MachineCategory;
use App\Models\LocationCategory;
use App\Models\Location;
use App\Models\Machine;
use App\Models\Equipment;
use App\Models\Tool;
use App\Models\Inspection;
use App\Models\GasMeasurement;

use App\Observers\CompanyObserver;
use App\Observers\CategoryGroupObserver;
use App\Observers\CategoryObserver;
use App\Observers\MachineCategoryObserver;
use App\Observers\LocationCategoryObserver;
use App\Observers\LocationObserver;
use App\Observers\MachineObserver;
use App\Observers\EquipmentObserver;
use App\Observers\ToolObserver;
use App\Observers\InspectionObserver;
use App\Observers\GasMeasurementObserver;

use App\Listeners\RegistrarInicioSesion;
use App\Listeners\RegistrarCierreSesion;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Company::observe(CompanyObserver::class);
        CategoryGroup::observe(CategoryGroupObserver::class);
        Category::observe(CategoryObserver::class);
        MachineCategory::observe(MachineCategoryObserver::class);
        LocationCategory::observe(LocationCategoryObserver::class);
        Location::observe(LocationObserver::class);
        Machine::observe(MachineObserver::class);
        Equipment::observe(EquipmentObserver::class);
        Tool::observe(ToolObserver::class);
        Inspection::observe(InspectionObserver::class);
        GasMeasurement::observe(GasMeasurementObserver::class);
    }
}