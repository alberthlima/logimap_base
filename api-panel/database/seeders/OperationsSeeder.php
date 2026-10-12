<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\Order;

class OperationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar registros previos
        Order::query()->delete();
        Client::query()->delete();

        $clientsData = [
            // --- EL ALTO ---
            [
                'name' => 'Doña Remedios',
                'surname' => 'Mamani',
                'business_name' => 'Micromarket Los Andes',
                'phone' => '71542389',
                'city' => 'El Alto',
                'district' => 'Distrito 1',
                'zone' => 'Ciudad Satélite',
                'address' => 'Av. Alfredo Sanjinés #450, Plan 112',
                'lat' => -16.5165000,
                'lng' => -68.1550000,
                'notes' => 'Atención de 07:00 a 22:00. Punto de entrega por puerta trasera.',
            ],
            [
                'name' => 'Carlos',
                'surname' => 'Quispe Alvarez',
                'business_name' => 'Distribuidora Ceja Express',
                'phone' => '72098412',
                'city' => 'El Alto',
                'district' => 'Distrito 1',
                'zone' => 'La Ceja',
                'address' => 'Calle 2 esquina Av. 6 de Marzo #120',
                'lat' => -16.5074000,
                'lng' => -68.1633000,
                'notes' => 'Zona de alto tráfico comercial. Recibe por las mañanas.',
            ],
            [
                'name' => 'Martha',
                'surname' => 'Condori Flores',
                'business_name' => 'Abarrotes Doña Martha',
                'phone' => '73256190',
                'city' => 'El Alto',
                'district' => 'Distrito 1',
                'zone' => 'Villa Dolores',
                'address' => 'Calle Antofagasta #245',
                'lat' => -16.5098000,
                'lng' => -68.1652000,
                'notes' => 'Pago contra entrega en efectivo.',
            ],
            [
                'name' => 'Gonzalo',
                'surname' => 'Ticona Perez',
                'business_name' => 'Mini Súper Santiago',
                'phone' => '74512398',
                'city' => 'El Alto',
                'district' => 'Distrito 2',
                'zone' => 'Santiago II',
                'address' => 'Plaza del Minero, Calle 5 #88',
                'lat' => -16.5412000,
                'lng' => -68.1695000,
                'notes' => 'Cliente frecuente de bebidas y lácteos.',
            ],
            [
                'name' => 'Beatriz',
                'surname' => 'Luna Huanca',
                'business_name' => 'Comercial Bolívar',
                'phone' => '76589012',
                'city' => 'El Alto',
                'district' => 'Distrito 2',
                'zone' => 'Villa Bolívar D',
                'address' => 'Av. Héroes del Km 7 #310',
                'lat' => -16.5204000,
                'lng' => -68.1750000,
                'notes' => 'Cerca del aeropuerto. Portón metálico azul.',
            ],
            [
                'name' => 'Javier',
                'surname' => 'Paredes Choque',
                'business_name' => 'Supermercado Familiar Adela',
                'phone' => '77894561',
                'city' => 'El Alto',
                'district' => 'Distrito 3',
                'zone' => 'Villa Adela',
                'address' => 'Av. Bolivia esquina Calle 12',
                'lat' => -16.5360000,
                'lng' => -68.1920000,
                'notes' => 'Horario preferido: 14:00 a 18:00.',
            ],
            [
                'name' => 'Elena',
                'surname' => 'Colque Ramos',
                'business_name' => 'Feria 16 de Julio Stand A-14',
                'phone' => '78912345',
                'city' => 'El Alto',
                'district' => 'Distrito 6',
                'zone' => '16 de Julio',
                'address' => 'Av. Alfonso Ugarte #560',
                'lat' => -16.4942000,
                'lng' => -68.1742000,
                'notes' => 'Días de entrega jueves y domingos.',
            ],
            [
                'name' => 'Félix',
                'surname' => 'Yujra Blanco',
                'business_name' => 'Depósito Río Seco',
                'phone' => '70123984',
                'city' => 'El Alto',
                'district' => 'Distrito 4',
                'zone' => 'Río Seco',
                'address' => 'Av. Juan Pablo II, Altura Cruce Río Seco',
                'lat' => -16.4851000,
                'lng' => -68.2045000,
                'notes' => 'Acceso directo para camión de reparto mediano.',
            ],
            [
                'name' => 'Rosario',
                'surname' => 'Canaviri Apaza',
                'business_name' => 'Tienda Huayna Potosí',
                'phone' => '79087612',
                'city' => 'El Alto',
                'district' => 'Distrito 5',
                'zone' => 'Huayna Potosí',
                'address' => 'Calle Litoral #820',
                'lat' => -16.4780000,
                'lng' => -68.1950000,
                'notes' => 'Frente a cancha deportiva.',
            ],
            [
                'name' => 'Hernán',
                'surname' => 'Vargas Machaca',
                'business_name' => 'Abarrotes Senkata Sur',
                'phone' => '71982345',
                'city' => 'El Alto',
                'district' => 'Distrito 8',
                'zone' => 'Senkata',
                'address' => 'Av. 6 de Marzo Km 14 #150',
                'lat' => -16.5750000,
                'lng' => -68.1820000,
                'notes' => 'Zona industrial y comercial en crecimiento.',
            ],

            // --- LA PAZ ---
            [
                'name' => 'Patricia',
                'surname' => 'Mercado Silva',
                'business_name' => 'Delicatessen Sopocachi',
                'phone' => '70654321',
                'city' => 'La Paz',
                'district' => 'COTAHUMA',
                'zone' => 'Sopocachi',
                'address' => 'Calle Belisario Salinas #380 (Plaza Abaroa)',
                'lat' => -16.5135000,
                'lng' => -68.1280000,
                'notes' => 'Pagos mediante transferencia QR. Carga rápida.',
            ],
            [
                'name' => 'Mauricio',
                'surname' => 'Del Carpio',
                'business_name' => 'Minimarket San Francisco',
                'phone' => '72567890',
                'city' => 'La Paz',
                'district' => 'CENTRO',
                'zone' => 'Centro Histórico',
                'address' => 'Calle Sagárnaga #195',
                'lat' => -16.4965000,
                'lng' => -68.1368000,
                'notes' => 'Paso peatonal. Descarga antes de las 09:30 AM.',
            ],
            [
                'name' => 'Silvia',
                'surname' => 'Rios Morales',
                'business_name' => 'Kiosco Miraflores Parque',
                'phone' => '73456712',
                'city' => 'La Paz',
                'district' => 'CENTRO',
                'zone' => 'Miraflores',
                'address' => 'Av. Busch esquina Calle Villalobos #110',
                'lat' => -16.5020000,
                'lng' => -68.1215000,
                'notes' => 'Punto cercano al Hospital de Clínicas.',
            ],
            [
                'name' => 'Rodrigo',
                'surname' => 'Guzmán Torrez',
                'business_name' => 'Comercial La Paz Plaza San Pedro',
                'phone' => '75019283',
                'city' => 'La Paz',
                'district' => 'COTAHUMA',
                'zone' => 'San Pedro',
                'address' => 'Calle Nicolás Acosta #412',
                'lat' => -16.5010000,
                'lng' => -68.1395000,
                'notes' => 'Recibe pedidos con factura emitida.',
            ],
            [
                'name' => 'Valeria',
                'surname' => 'Arce Peñaranda',
                'business_name' => 'Express Gourmet Calacoto',
                'phone' => '76210984',
                'city' => 'La Paz',
                'district' => 'SUR',
                'zone' => 'Calacoto',
                'address' => 'Calle 15 de Calacoto esquina Av. Ballivián #85',
                'lat' => -16.5410000,
                'lng' => -68.0875000,
                'notes' => 'Estacionamiento para descarga habilitado.',
            ],
            [
                'name' => 'Esteban',
                'surname' => 'Chávez Justiniano',
                'business_name' => 'Bodega Central Obrajes',
                'phone' => '77123049',
                'city' => 'La Paz',
                'district' => 'SUR',
                'zone' => 'Obrajes',
                'address' => 'Calle 8 de Obrajes #230',
                'lat' => -16.5270000,
                'lng' => -68.1120000,
                'notes' => 'Recibe pedidos por volumen semanal.',
            ],
            [
                'name' => 'Gladys',
                'surname' => 'Mamani Laura',
                'business_name' => 'Feria Garita de Lima Local 4',
                'phone' => '78456123',
                'city' => 'La Paz',
                'district' => 'MAX PAREDES',
                'zone' => 'Max Paredes',
                'address' => 'Av. Buenos Aires esq. Max Paredes #510',
                'lat' => -16.4925000,
                'lng' => -68.1480000,
                'notes' => 'Comercio mayorista de abarrotes.',
            ],
            [
                'name' => 'Wilmer',
                'surname' => 'Quisbert Alanoca',
                'business_name' => 'Distribuidora Villa Fátima',
                'phone' => '77234567',
                'city' => 'La Paz',
                'district' => 'PERIFERICA',
                'zone' => 'Villa Fátima',
                'address' => 'Av. Las Américas #720',
                'lat' => -16.4603000,
                'lng' => -68.1213000,
                'notes' => 'Entrega de abarrotes para la ruta a los Yungas.',
            ],
            [
                'name' => 'Carla',
                'surname' => 'Mendoza Conde',
                'business_name' => 'Micromarket San Antonio Central',
                'phone' => '76543210',
                'city' => 'La Paz',
                'district' => 'SAN ANTONIO',
                'zone' => 'San Antonio Bajo',
                'address' => 'Av. 31 de Octubre #450',
                'lat' => -16.4889000,
                'lng' => -68.1015000,
                'notes' => 'Tienda de barrio de alta rotación.',
            ],
            [
                'name' => 'Fernando',
                'surname' => 'Salinas Rico',
                'business_name' => 'Almacén Valle de la Luna',
                'phone' => '78901234',
                'city' => 'La Paz',
                'district' => 'MALLASA',
                'zone' => 'Mallasa',
                'address' => 'Av. Florida #180, frente al Zoológico',
                'lat' => -16.5650000,
                'lng' => -68.0780000,
                'notes' => 'Entregas matutinas en zona turística/residencial.',
            ],
        ];

        $createdClients = [];
        foreach ($clientsData as $c) {
            $createdClients[] = Client::create($c);
        }

        // Crear Pedidos representativos para cada cliente
        $statuses = ['REGISTRADO', 'EN_CAMINO', 'ENTREGADO', 'PAGADO'];
        $drivers = ['Rodrigo Ramos (Moto 1)', 'Marcelo Huanca (Camioneta 3)', 'David Choque (Furgón 2)', 'Alvaro Cruz (Moto 4)'];
        $items = [
            '10x Aceite Fino 900ml, 5x Harina Blancaflor 1kg, 20x Fideo Corona',
            '15x Arroz Carolina 5kg, 12x Azúcar Guabirá 1kg, 8x Leche Pil 1L',
            '25x Galletas Mabel surtidas, 10x Café Buena Vista 250g, 30x Jugos del Valle',
            '50x Cerveza Paceña Huari pack, 40x Gaseosa Coca-Cola 2L, 20x Agua Vital',
            '6x Packs Detergente Omo 3kg, 12x Jaboncillo Bolívar, 20x Papel Higiénico Nacional',
            '18x Cajas Atún Van Camps, 15x Mayonesa Kris 500g, 10x Kétchup Kris 500g',
        ];

        $codeNum = 1001;
        foreach ($createdClients as $index => $client) {
            // Asignar un estado según el índice para tener variedad balanceada
            $status = $statuses[$index % 4];
            $driver = ($status === 'REGISTRADO') ? null : $drivers[$index % count($drivers)];
            $amount = round(rand(280, 2650) + (rand(10, 95) / 100), 2);
            $methods = ['Efectivo', 'QR Simple', 'Transferencia', 'Crédito 15 días'];

            Order::create([
                'code' => 'PED-' . $codeNum++,
                'client_id' => $client->id,
                'total_amount' => $amount,
                'payment_method' => $methods[$index % count($methods)],
                'status' => $status,
                'city' => $client->city,
                'district' => $client->district,
                'delivery_address' => $client->address,
                'lat' => $client->lat,
                'lng' => $client->lng,
                'delivery_driver' => $driver,
                'items_summary' => $items[$index % count($items)],
                'notes' => 'Pedido asignado desde portal central. ' . $client->notes,
                'delivery_date' => now()->addDays($index % 3)->format('Y-m-d'),
            ]);
        }
    }
}
