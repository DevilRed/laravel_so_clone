<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Http\Resources\QuestionResource;
use App\Http\Resources\UserResource;
use App\Models\Question;
use App\Models\Vote;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QuestionController extends Controller
{
    public function index()
    {
        return QuestionResource::collection(
            Question::with(['answers', 'user'])
                ->latest()->paginate(3)
        );
    }

    /**
     * get logged in user questions
     */
    public function authUserQuestions(Request $request)
    {
        return QuestionResource::collection(
            $request->user()->questions()
                ->with(['answers', 'user'])
                ->latest()->paginate(3)
        );
    }

    /**
     * get questions by given user
     */
    public function questionsByUser(Request $request)
    {
        $user = User::find($request->user_id);
        return QuestionResource::collection(
            $user->questions()
                ->with(['answers', 'user'])
                ->latest()->paginate(3)
        );
    }

    /**
     * get questions by given tag
     */
    public function questionsByTag($tag)
    {
        $questions = Question::where('tags', 'like', '%' . $tag . '%')
            ->with(['answers', 'user'])
            ->latest()->paginate(3);
        return QuestionResource::collection($questions);
    }

    /**
     * get questions by slug
     */
    public function show(Question $question)
    {
        if (!$question) {
            abort(404);
        }
        $question->increment('viewCount');
        return QuestionResource::make($question->load(['answers', 'user']));
    }

    /**
     * store a new question
     */
    public function store(StoreQuestionRequest $request)
    {
        $data = $request->validated();
        // add slug, tags since they are not required in the request
        $data['slug'] = Str::slug($data['title']);
        $data['tags'] = $data['tags'] ?? null;
        // use association go create the question
        $question = $request->user()->questions()->create($data);
        return QuestionResource::make($question)->additional([
            'message' => 'Question created successfully'
        ]);
    }

    /**
     * update question
     */
    public function update(UpdateQuestionRequest $request, Question $question)
    {
        if ($request->user()->cannot('update', $question)) {
            return response()->json([
                'error' => 'You are not authorized to update this question',
                'user' => UserResource::make($request->user())
            ], 403);
        } else {
            $data = $request->validated();
            $data['slug'] = Str::slug($data['title']);
            $data['tags'] = $data['tags'] ?? $question->tags;
            $question->update($data);
            return QuestionResource::make($question)->additional([
                'message' => 'Question updated successfully',
                'user' => UserResource::make($request->user())
            ]);
        }
    }

    /**
     * delete question
     */
    public function destroy(Request $request, Question $question)
    {
        if ($request->user()->cannot('update', $question)) {
            return response()->json([
                'error' => 'You are not authorized to delete this question',
                'user' => UserResource::make($request->user())
            ], 403);
        } else {
            $question->delete();
            return response()->json([
                'message' => 'Question deleted successfully',
                'user' => UserResource::make($request->user())
            ]);
        }
    }

    /**
     * vote for a question, type: up or down
     */
    public function vote(Request $request, Question $question, $type)
    {
        // Validate the vote type
        if (!in_array($type, ['up', 'down'])) {
            return response()->json([
                'error' => 'Invalid vote type',
            ], 400);
        }
        // check if user already voted for the question
        $existingVote = $question->votes()
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existingVote) {
            return response()->json([
                'error' => 'You already voted for this question',
                'user' => UserResource::make($request->user())
            ], 400);
        }
        if ($type == 'up') {
            $question->increment('score');
        } else {
            $question->decrement('score');
        }
        $vote = new Vote([
            'user_id' => $request->user()->id,
            'type' => $type,
        ]);
        $question->votes()->save($vote);

        // Dynamically calculate the total votes
        $upVotes = $question->votes()->where('type', 'up')->count();
        $downVotes = $question->votes()->where('type', 'down')->count();
        $netVotes = $upVotes - $downVotes;

        return QuestionResource::make($question->load(['answers', 'user']))->additional([
            'message' => 'Vote added successfully',
            'votes_count' => $netVotes,
        ]);
    }
}
