<?php

namespace App\Http\Controllers;

use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function createQuestion(Request $request)
    {
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

    public function getAllQuestions()
    {
        $questions = Question::all();

        return response()->json([
            'message' => 'All questions retrieved successfully',
            'questions' => $questions
        ]);
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
}
