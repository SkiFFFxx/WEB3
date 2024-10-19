<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Achievement;
use Illuminate\Support\Facades\Log;

class QuestionController extends Controller
{
    public function createQuestion(Request $request)
    {
        #sdsdsd
        $validated = $request->validate([
            'question' => 'required|string',
            'option1' => 'required|string',
            'option2' => 'required|string',
            'option3' => 'required|string',
            'correct_answer' => 'required|integer|min:1|max:3'
        ]);

        $question = Question::create([
            'question' => $validated['question'],
            'option1' => $validated['option1'],
            'option2' => $validated['option2'],
            'option3' => $validated['option3'],
            'correct_answer' => $validated['correct_answer'],
        ]);

        return response()->json(['message' => 'Question created successfully', 'question' => $question]);
    }

    public function createAchievementIfNotExists()
    {
        // Проверяем, существует ли достижение
        $achievement = Achievement::where('name', 'First Time Access Questions')->first();

        if (!$achievement) {
            // Если достижение не найдено, создаем его
            $achievement = Achievement::create([
                'name' => 'First Time Access Questions',
                'description' => 'Достижение за первое открытие вопросов'
            ]);
            Log::info('Achievement "First Time Access Questions" created successfully');
        }

        return $achievement;
    }

    public function getAllQuestions(Request $request)
    {
        // Получаем telegram_id из запроса
        $telegramId = $request->input('telegram_id');

        // Проверяем, передан ли telegram_id
        if (!$telegramId) {
            return response()->json([
                'message' => 'Telegram ID is required'
            ], 400); // Возвращаем ошибку, если telegram_id не передан
        }

        // Ищем пользователя с данным telegram_id
        $user = User::where('telegram_id', $telegramId)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User with this Telegram ID not found'
            ], 404); // Возвращаем ошибку, если пользователь не найден
        }

        // Переменные для отслеживания статуса и ачивки
        $statusUpdated = false;
        $achievementGranted = false;

        // Проверяем, открыл ли пользователь вопросы впервые
        if ($user->status == 'new') {
            // Меняем статус на "opened_questions"
            $user->status = 'opened_questions';
            $user->save();
            $statusUpdated = true;

            Log::info('User status updated to opened_questions for telegram_id: ' . $telegramId);

            // Создаем достижение, если его нет
            $achievement = $this->createAchievementIfNotExists();

            // Проверяем, если у пользователя уже нет этого достижения, то добавляем его
            if ($achievement && !$user->achievements()->find($achievement->id)) {
                $user->achievements()->attach($achievement->id, ['achieved_at' => now()]);
                $achievementGranted = true;
                Log::info('Achievement "First Time Access Questions" granted to user with telegram_id: ' . $telegramId);
            }
        }

        // Получаем все вопросы
        $questions = Question::all();

        // Формируем ответ с дополнительной информацией
        return response()->json([
            'message' => 'All questions retrieved successfully',
            'questions' => $questions,
            'user_info' => [
                'telegram_id' => $user->telegram_id,
                'status' => $user->status,
                'status_updated' => $statusUpdated ? 'Status was updated to "opened_questions"' : 'Status remains unchanged',
                'achievement_granted' => $achievementGranted ? 'Achievement "First Time Access Questions" was granted' : 'No new achievements'
            ]
        ], 200);
    }


    public function updateQuestion(Request $request, $id)
    {
        $question = Question::find($id);

        if (!$question) {
            return response()->json(['message' => 'Question not found'], 404);
        }

        $validated = $request->validate([
            'question' => 'sometimes|string',
            'option1' => 'sometimes|string',
            'option2' => 'sometimes|string',
            'option3' => 'sometimes|string',
            'correct_answer' => 'sometimes|integer|min:1|max:3'
        ]);

        $question->update($validated);

        return response()->json([
            'message' => 'Question updated successfully',
            'question' => $question
        ]);
    }

    public function deleteQuestion($id)
    {
        $question = Question::find($id);

        if (!$question) {
            return response()->json(['message' => 'Question not found'], 404);
        }

        $question->delete();

        return response()->json(['message' => 'Question deleted successfully']);
    }

    public function checkAnswers(Request $request)
    {
        $answers = $request->input('answers'); // answers должен быть массивом вида [question_id => user_answer]

        $results = [];
        foreach ($answers as $questionId => $userAnswer) {
            $question = Question::find($questionId);
            if (!$question) {
                $results[$questionId] = ['correct' => false, 'error' => 'Question not found'];
                continue;
            }

            $correct = $question->correct_answer == $userAnswer;
            $results[$questionId] = [
                'correct' => $correct,
                'provided_answer' => $userAnswer,
                'correct_answer' => $question->correct_answer
            ];
        }

        return response()->json([
            'results' => $results
        ]);
    }


    public function getUserAchievements(Request $request)
    {
        // Получаем telegram_id из запроса
        $telegramId = $request->input('telegram_id');

        // Проверяем, передан ли telegram_id
        if (!$telegramId) {
            return response()->json([
                'message' => 'Telegram ID is required'
            ], 400); // Возвращаем ошибку, если telegram_id не передан
        }

        // Ищем пользователя с данным telegram_id
        $user = User::where('telegram_id', $telegramId)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User with this Telegram ID not found'
            ], 404); // Возвращаем ошибку, если пользователь не найден
        }

        // Получаем все возможные достижения
        $totalAchievements = Achievement::count();

        // Получаем достижения, которые пользователь уже заработал
        $userAchievements = $user->achievements()->get();

        // Подсчитываем количество заработанных достижений
        $achievementsCount = $userAchievements->count();

        // Формируем список достижений с детальной информацией, включая achieved_at
        $achievementsList = $userAchievements->map(function ($achievement) {
            return [
                'name' => $achievement->name,
                'description' => $achievement->description,
                'achieved_at' => $achievement->pivot->achieved_at // Дата получения достижения
            ];
        });

        return response()->json([
            'message' => 'Achievements retrieved successfully',
            'achievements_count' => "{$achievementsCount} of {$totalAchievements}",
            'achievements' => $achievementsList
        ]);
    }

}
