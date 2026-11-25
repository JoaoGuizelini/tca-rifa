<?php

namespace Database\Seeders;

use App\Models\Role;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionSeeder::class);
        if (DB::table('users')->exists()) {
            return;
        }
        DB::transaction(function () {
            $accounts = [['Administração', 'admin', 'administrador', '52998224725'], ['Oficina Aurora', 'aurora', 'organizador', '11144477735'], ['Alex Silva', 'alex', 'participante', '12345678909']];
            foreach ($accounts as [$name,$alias,$role,$cpf]) {
                $pw = Str::password(16, symbols: false);
                $ids[$alias] = DB::table('users')->insertGetId(['name' => $name, 'email' => $alias.'@bilhete.test', 'cpf' => $cpf, 'phone' => '11900000000', 'role_id' => Role::where('name', $role)->firstOrFail()->id, 'password' => Hash::make($pw), 'created_at' => now(), 'updated_at' => now()]);
                $this->command->info($alias.'@bilhete.test  '.$pw);
            }
            DB::table('organizers')->insert(['user_id' => $ids['aurora'], 'public_name' => 'Oficina Aurora', 'bio' => 'Coletivo de bairro que reúne esporte, música e fotografia em projetos culturais.', 'document' => '', 'status' => 'aprovado']);
            $samples = [['Esporte', 'Bicicleta de passeio', 'Bicicleta urbana com quadro de aço', 'bike', 500], ['Música', 'Som de estúdio', 'Fones de ouvido de alta fidelidade', 'sound', 350], ['Fotografia', 'Câmera analógica', 'Câmera de filme 35 mm', 'camera', 750]];
            foreach ($samples as [$category,$title,$prize,$art,$price]) {
                $k = DB::table('categories')->insertGetId(['name' => $category]);
                $id = DB::table('campaigns')->insertGetId(['owner_id' => $ids['aurora'], 'category_id' => $k, 'title' => $title, 'description' => 'Campanha da Oficina Aurora para equipar o espaço de oficinas abertas do bairro. O item desta edição é '.mb_strtolower($prize).'.', 'prize' => $prize,
                    'rules' => 'Escolha seus números e confirme a reserva em 15 minutos. O organizador publicará o número contemplado após o encerramento. Demonstração acadêmica, sem cobrança.',
                    'quantity' => 100, 'price' => $price, 'starts' => today()->subDay(), 'ends' => today()->addDays(30), 'status' => 'ativa', 'photos' => json_encode(['/art/'.$art.'.svg']), 'created_at' => now(), 'updated_at' => now()]);
                $rows = [];
                for ($i = 1; $i <= 100; $i++) {
                    $rows[] = ['campaign_id' => $id, 'number' => $i];
                } DB::table('tickets')->insert($rows);
            }
        });
    }
}
