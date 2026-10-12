<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::withCount('orders')->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('surname', 'like', "%{$search}%")
                  ->orWhere('business_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('zone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        if ($request->filled('district')) {
            $query->where('district', $request->district);
        }

        if ($request->boolean('all')) {
            return response()->json([
                'clients' => $query->get(),
            ]);
        }

        $perPage = $request->input('per_page', 15);
        $clients = $query->paginate($perPage);

        return response()->json([
            'clients' => $clients->items(),
            'total' => $clients->total(),
            'current_page' => $clients->currentPage(),
            'last_page' => $clients->lastPage(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'business_name' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:30',
            'city' => 'required|string|max:50',
            'district' => 'required|string|max:50',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'address' => 'nullable|string|max:255',
            'zone' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $client = Client::create($request->all());

        return response()->json([
            'message' => 'Cliente registrado correctamente',
            'client' => $client,
        ], 201);
    }

    public function show($id)
    {
        $client = Client::with('orders')->findOrFail($id);

        return response()->json([
            'client' => $client,
        ]);
    }

    public function update(Request $request, $id)
    {
        $client = Client::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'city' => 'required|string|max:50',
            'district' => 'required|string|max:50',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $client->update($request->all());

        return response()->json([
            'message' => 'Cliente actualizado correctamente',
            'client' => $client,
        ]);
    }

    public function destroy($id)
    {
        $client = Client::findOrFail($id);
        $client->delete();

        return response()->json([
            'message' => 'Cliente eliminado correctamente',
        ]);
    }
}
