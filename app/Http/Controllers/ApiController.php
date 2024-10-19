<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use App\Models\Achievement;

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


    public function createAchievement()
    {
        // Проверяем, существует ли достижение с таким названием
        $achievement = Achievement::where('name', 'First Time Access Questions')->first();

        if ($achievement) {
            return response()->json([
                'message' => 'Achievement already exists',
                'achievement' => $achievement
            ], 200);
        }

        // Создаем достижение, если оно не существует
        $achievement = Achievement::create([
            'name' => 'First Time Access Questions',
            'description' => 'Достижение за первое открытие вопросов'
        ]);

        return response()->json([
            'message' => 'Achievement created successfully',
            'achievement' => $achievement
        ], 201);
    }


    public function getUserProfile(Request $request)
    {
        // Получаем telegram_id из запроса
        $telegramId = $request->input('telegram_id');

        // Проверяем, передан ли telegram_id
        if (!$telegramId) {
            return response()->json([
                'message' => 'Telegram ID is required'
            ], 400);
        }

        // Ищем пользователя с данным telegram_id
        $user = User::where('telegram_id', $telegramId)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User with this Telegram ID not found'
            ], 404);
        }

        // Получаем достижения пользователя
        $userAchievements = $user->achievements()->get();
        $achievementsCount = $userAchievements->count();

        // Получаем общее количество достижений, которые существуют
        $totalAchievements = Achievement::count();

        // Формируем список достижений
        $achievementsList = $userAchievements->map(function ($achievement) {
            return [
                'name' => $achievement->name,
                'description' => $achievement->description,
                'achieved_at' => $achievement->pivot->achieved_at
            ];
        });

        // Формируем ответ с данными для личного кабинета
        return response()->json([
            'message' => 'User profile retrieved successfully',
            'profile' => [
                'telegram_id' => $user->telegram_id,
                'name' => $user->name,
                'created_at' => $user->created_at, // Опционально
                'achievements' => $achievementsList,
                'achievements_count' => "Ачивок получено {$achievementsCount} из {$totalAchievements}",
            ]
        ], 200);
    }









}
