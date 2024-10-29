<x-app-layout>
    <div class="max-w-2xl p-4 mx-auto sm:p-6 lg:p-8">
        <h1 class="text-2xl font-bold">{{ $chirp->message }}</h1>
        <p class="text-sm text-gray-600">Posted by
            {{ $chirp->user->name ?? 'Anonymous' }} on
            {{ $chirp->created_at->format('j M Y, g:i a') }}</p>

        @if(!empty($filePaths))
            <h2 class="mt-4 text-lg font-semibold">Attached Files:</h2>
            <div class="grid grid-cols-4 sm:grid-cols-4 gap-8 mt-2"> <!-- Adjusted grid structure -->
                @foreach($filePaths as $filePath)
                    <div class="flex flex-col items-center">
                        @php
                            $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
                            $baseFileName = basename($filePath);
                            // Truncate the filename if it's longer than 5 characters
                            $displayFileName = (strlen($baseFileName) > 5) ? substr($baseFileName, 0, 5) . '...' . $fileExtension : $baseFileName;
                        @endphp

                        @if(in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']))
                            <a href="/storage/{{ basename($filePath) }}" data-fancybox="gallery" data-caption="{{ $baseFileName }}">
                                <img src="/storage/{{ basename($filePath) }}" alt="{{ $baseFileName }}" class="object-cover w-24 h-24 rounded-lg"> <!-- Adjusted size -->
                            </a>
                        @else
                            <a href="https://d9-wret.s3.us-west-2.amazonaws.com/assets/palladium/production/s3fs-public/thumbnails/image/file.jpg" data-fancybox="gallery" data-caption="{{ $baseFileName }}">
                                <div class="flex items-center justify-center w-24 h-24 text-sm text-gray-600 bg-gray-200 rounded-lg"> <!-- Adjusted size -->
                                    <img src="https://d9-wret.s3.us-west-2.amazonaws.com/assets/palladium/production/s3fs-public/thumbnails/image/file.jpg" alt="File Placeholder" class="object-cover w-full h-full rounded-lg">
                                </div>
                            </a>
                        @endif

                        <span class="mt-2 text-sm text-gray-700 text-center">{{ $displayFileName }}</span>

                        <a href="{{ route('chirps.shared.downloadFile', ['token' => $chirp->share_token, 'file' => basename($filePath)]) }}"
                           class="mt-2 text-gray-400 hover:text-gray-800">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 p-1 bg-gray-200 rounded-lg"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12.75m0 0l-3-3m3 3l3-3m5 5H4" />
                            </svg>
                        </a>
                    </div>
                @endforeach
            </div>

            @if(count($filePaths) > 1)
                <div class="sticky bottom-0 left-0 right-0 bg-white p-4 shadow-md z-10 mt-12">
                    <a href="{{ route('chirps.shared.download', ['token' => $chirp->share_token]) }}" class="block w-full px-4 py-2 text-center text-white bg-blue-500 rounded hover:bg-blue-700">
                        Download All Shared Files as ZIP
                    </a>
                </div>
            @endif
            
        @else
            <p>No files associated with this chirp.</p>
        @endif
    </div>
</x-app-layout>
