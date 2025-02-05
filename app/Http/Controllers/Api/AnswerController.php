<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnswerRequest;
use App\Http\Requests\UpdateAnswerRequest;
use App\Http\Resources\AnswerResource;
use App\Http\Resources\QuestionResource;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Vote;
use Illuminate\Http\Request;

class AnswerController extends Controller
{
    /**
     * get answers by slug
     */
    public function show(Answer $answer)
    {
        if (!$answer) {
            abort(4040);
        }
        return AnswerResource::make($answer->load(['question']));
    }

    /**
     * store a new answer
     */
    public function store(StoreAnswerRequest $request, Question $question)
    {
        $data = $request->validated();
        $data['question_id'] = $question->id;
        $request->user()->answers()->create($data);
        return QuestionResource::make($question->load(['user', 'answers']))->additional([
            'message' => 'Answer created successfully'
        ]);
    }

    /**
     * update answer
     */
    public function update(UpdateAnswerRequest $request, Question $question, Answer $answer)
    {
        if ($request->user()->cannot('update', $answer)) {
            return response()->json(['error' => 'You are not authorized to update this answer',
            ], 403);
        } else {
            $data = $request->validated();
            $data['user_id'] = $request->user()->id;
            $data['question_id'] = $question->id;
            $answer->update($data);
            return QuestionResource::make($question)->additional([
                'message' => 'Answer updated successfully',
            ]);
        }
    }

    /**
     * delete answer
     */
    public function destroy(Request $request, Question $question, Answer $answer)
    {
        if ($request->user()->cannot('update', $answer)) {
            return response()->json([
                'error' => 'Something went wrong',
            ], 403);
        } else {
            $answer->delete();
            return QuestionResource::make($question->load(['user', 'answers']))->additional([
                'message' => 'Answer deleted successfully',
            ]);
        }
    }

    /**
     * vote for an answer, type if up or down
     */
    public function vote(Request $request, Answer $answer, $type)
    {
        // Validate the vote type
        if (!in_array($type, ['up', 'down'])) {
            return response()->json([
                'error' => 'Invalid vote type',
            ], 400);
        }
        // check if user already voted for the answer
        $existingVote = $answer->votes()
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existingVote) {
            return response()->json([
                'error' => 'You already voted for this answer',
            ], 400);
        }
        if ($type == 'up') {
            $answer->increment('score');
        } else {
            $answer->decrement('score');
        }
        $vote = new Vote([
            'user_id' => $request->user()->id,
            'type' => $type,
        ]);
        $answer->votes()->save($vote);

        // Dynamically calculate the total votes
        $upVotes = $answer->votes()->where('type', 'up')->count();
        $downVotes = $answer->votes()->where('type', 'down')->count();
        $netVotes = $upVotes - $downVotes;
        $question = Question::find($answer->question_id);

        return QuestionResource::make($question->load(['answers', 'user']))->additional([
            'message' => 'Vote added successfully',
            'votes_count' => $netVotes,
        ]);
    }

    /**
     * Mark an answer as the best
     */
    public function markAsBest(Request $request, Answer $answer)
    {
        // only the question owner can mark an answer as best
        if ($request->user()->cannot('markAsBest', $answer)) {
            return response()->json(
                [
                    'error' => 'Something went wrong try again later',
                ],
                403
            );
        }
        // remove previous best answer if exists
        $prevBestAnswer = Answer::where(['best_answer' => 1, 'question_id' => $answer->question_id])->first();
        if ($prevBestAnswer) {
            if ($prevBestAnswer->id === $answer->id) {
                return response()->json(
                    [
                        'message' => 'This answer is already marked as best',
                    ],
                    200
                );
            }
            $prevBestAnswer->best_answer = 0;
            $prevBestAnswer->save();
        }
        $answer->best_answer = 1;
        $answer->save();
        return QuestionResource::make($answer->question->load(['answers', 'user']))->additional([
            'message' => 'Answer marked as best',
        ]);
    }
}
