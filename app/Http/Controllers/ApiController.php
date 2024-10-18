<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class ApiController extends Controller
{
    // Предполагаем, что этот метод уже внутри UserController

    public function createUser(Request $request)
    {
        Log::info('Attempting to create user with Telegram ID: ' . $request->telegram_id);

        try {
            $validated = $request->validate([
                'telegram_id' => 'required|unique:users,telegram_id',
                'name' => 'required',
                'email' => 'nullable|email|unique:users,email',
                'password' => 'nullable'
            ]);

            $user = User::create([
                'telegram_id' => $validated['telegram_id'],
                'name' => $validated['name'],
                'email' => $request->get('email'),
                'password' => $request->get('password') ? bcrypt($request->get('password')) : null,
            ]);

            Log::info('User created successfully: ' . $user->id);

            return response()->json([
                'message' => 'User created successfully',
                'user' => $user
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error: ' . $e->getMessage());
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (QueryException $e) {
            Log::error('Database error: ' . $e->getMessage());
            return response()->json(['message' => 'Database error', 'error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error('Server error: ' . $e->getMessage());
            return response()->json(['message' => 'Server error', 'error' => $e->getMessage()], 500);
        }
    }


    public function getUsers()
    {
        $users = User::limit(50)->get(); // Получаем до 50 пользователей
        return response()->json(['users' => $users]);
    }


    public function updateUser(Request $request, $id)
    {
        Log::info('Attempting to update user with ID: ' . $id);

        try {
            $user = User::findOrFail($id); // Используем findOrFail чтобы автоматически вернуть 404 если пользователь не найден

            $validated = $request->validate([
                'name' => 'sometimes|string',
                'email' => 'sometimes|email|unique:users,email,' . $id, // Убеждаемся, что email уникален, исключая текущий ID пользователя
                'password' => 'nullable'
            ]);

            // Обновляем данные пользователя
            $user->update([
                'name' => $request->get('name', $user->name), // Используем текущее значение, если новое не предоставлено
                'email' => $request->get('email', $user->email),
                'password' => $request->filled('password') ? bcrypt($request->password) : $user->password,
            ]);

            Log::info('User updated successfully: ' . $user->id);

            return response()->json([
                'message' => 'User updated successfully',
                'user' => $user
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error: ' . $e->getMessage());
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (QueryException $e) {
            Log::error('Database error: ' . $e->getMessage());
            return response()->json(['message' => 'Database error', 'error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error('Server error: ' . $e->getMessage());
            return response()->json(['message' => 'Server error', 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteUser($id)
    {
        Log::info('Attempting to delete user with ID: ' . $id);

        try {
            $user = User::findOrFail($id);  // Используем findOrFail чтобы автоматически вернуть 404 если пользователь не найден

            $user->delete();  // Удаление пользователя

            Log::info('User deleted successfully: ' . $id);

            return response()->json(['message' => 'User deleted successfully'], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('User not found: ' . $e->getMessage());
            return response()->json(['message' => 'User not found'], 404);
        } catch (\Exception $e) {
            Log::error('Server error: ' . $e->getMessage());
            return response()->json(['message' => 'Server error', 'error' => $e->getMessage()], 500);
        }
    }




}
