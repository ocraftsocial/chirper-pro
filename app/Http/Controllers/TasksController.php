<?php


namespace App\Http\Controllers;


    use Illuminate\View\View;
    use App\Models\Tasks;
    use Illuminate\Http\Request;

    class TasksController extends Controller
    {
        /**
         * Display a listing of the resource.
         */public function index()
{
    $tasks = Tasks::all();
    return view('tasks.index', compact('tasks'));
}

public function store(Request $request)
{
    $request->validate(['title' => 'required|string|max:255']);
    Tasks::create($request->only('title'));
    return redirect()->route('tasks.index');
}

public function destroy(Tasks $task)
{
    $task->delete();
    return redirect()->route('tasks.index');
}

public function toggle(Tasks $task)
{
    $task->completed = !$task->completed;
    $task->save();
    return redirect()->route('tasks.index');
}

public function reset()
{
    Tasks::query()->update(['completed' => false]);
    return redirect()->route('tasks.index');
}

    }
