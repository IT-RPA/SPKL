<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProcessType;

class ProcessTypeSeeder extends Seeder
{
    public function run()
    {
        $types = [
            ['code' => 'REPAIR', 'name' => 'Repair', 'description' => 'Perbaikan mesin atau equipment'],
            ['code' => 'MONITOR', 'name' => 'Monitoring', 'description' => 'Monitoring proses produksi'],
            ['code' => 'TRAINING', 'name' => 'Training', 'description' => 'Pelatihan karyawan'],
            ['code' => 'WELDING', 'name' => 'Welding', 'description' => 'Proses pengelasan'],
        ];

        foreach ($types as $type) {
            ProcessType::create($type);
        }
    }
}