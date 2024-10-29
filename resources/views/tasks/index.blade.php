<x-app-layout>
    <div class="py-12" dir="rtl">
        <div class="max-w-2xl p-4 mx-auto sm:p-6 lg:p-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-2xl font-bold mb-4">משימות</h1>
                    
                    <form action="{{ route('tasks.store') }}" method="POST" class="mb-4 flex">
                        @csrf
                        <input type="text" name="title" required class="border-gray-300 border rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 flex-1 p-2" placeholder="כתוב כאן משהו מעניין..">
                        <button type="submit" class="bg-black text-white rounded-md px-4 py-2 mr-2 hover:bg-gray-800 transition duration-200">הוסף</button>
                    </form>

                    <form action="{{ route('tasks.reset') }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit" class="bg-black text-white rounded-md px-4 py-2 hover:bg-gray-800 transition duration-200">איפוס כל המשימות</button>
                    </form>

                    <ul class="mt-4">
                        @foreach ($tasks as $task)
                            <li class="flex items-center justify-between mb-2 p-2 border-b border-gray-200">
                                <form action="{{ route('tasks.toggle', $task) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('PATCH')
                                    <input type="checkbox" onchange="this.form.submit()" class="appearance-none w-6 h-6 border-2 border-gray-300 rounded-md checked:bg-black checked:border-transparent focus:outline-none ml-2 mb-1" {{ $task->completed ? 'checked' : '' }}>
                                    <span class="{{ $task->completed ? 'line-through text-gray-400' : 'text-gray-900' }}">{{ $task->title }}</span>
                                </form>
                                <form action="{{ route('tasks.destroy', $task) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-black text-white rounded-md px-4 py-2 hover:bg-gray-800 transition duration-200">הסר</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
