<?php

namespace Database\Seeders;

use App\Models\ChatDataset;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ChatDatasetSeeder extends Seeder
{
    public function run()
    {
        $path = storage_path('app/chat_dataset.json');   

        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);

            foreach ($data as $item) {
                ChatDataset::updateOrCreate(
                    ['keyword' => $item['keyword']],
                    ['response' => $item['response']]
                );
            }

            $this->command->info('✅ Chat dataset imported successfully!');
        } else {
            $this->command->error('⚠️ chat_dataset.json not found!');
        }
    }
}
