<x-app-layout>
    <div class="max-w-2xl p-4 mx-auto sm:p-6 lg:p-8">
        <form method="POST" action="{{ route('chirps.store') }}" enctype="multipart/form-data" id="chirp-form">
            @csrf
            <textarea name="message"
                placeholder="{{ __('What\'s on your mind?') }}"
                class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">{{ old('message') }}</textarea>
            <x-input-error :messages="$errors->get('message')" class="mt-2" />

            <!-- File Upload Section -->
            <div class="mb-4">
                <label for="files" class="block text-sm font-medium text-gray-700">Choose Files</label>
                <input type="file" name="files[]" id="files" class="w-full px-4 py-2 mt-2 rounded" multiple onchange="previewFiles()">
            </div>
            <x-input-error :messages="$errors->get('files')" class="mt-2" />

            <!-- Preview Area for Files -->
            <div id="file-preview" class="flex flex-wrap gap-4 mt-4"></div>

            <x-primary-button class="mt-4">{{ __('Chirp') }}</x-primary-button>
        </form>

        <div class="mt-6 bg-white divide-y rounded-lg shadow-sm">
            @foreach($chirps as $chirp)
                <div class="flex p-6 space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-600 -scale-x-100" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>

                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-gray-800">{{ $chirp->user->name }}</span>
                                <small class="ml-2 text-sm text-gray-600">{{ $chirp->created_at->format('j M Y, g:i a') }}</small>
                            </div>
                        </div>

                        <p class="mt-4 text-lg text-gray-900">{{ $chirp->message }}</p>

                        @if($chirp->files)
                            <div class="mt-4">
                                <h3 class="mb-2 text-sm text-gray-700 bg-gray-100">Uploaded Files:</h3>
                                <div class="flex flex-wrap gap-4">
                                    @php
                                        $files = is_array($chirp->files) ? $chirp->files : json_decode($chirp->files, true);
                                    @endphp

                                    @foreach($files as $file)
                                        @php
                                            $isImage = in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif']);
                                            $fileName = basename($file);
                                            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                                            $shortDescription = substr($fileName, 0, 5) . '.' . $fileExtension;
                                        @endphp
                                        <div class="flex flex-col items-center">
                                            @if($isImage)
                                                <a href="{{ route('chirps.image', ['id' => $chirp->id, 'file' => $fileName]) }}" target="_blank" class="block">
                                                    <img src="{{ route('chirps.image', ['id' => $chirp->id, 'file' => $fileName]) }}" alt="Chirp Image" class="object-cover w-16 h-16 rounded-lg">
                                                </a>
                                            @else
                                                <div class="flex items-center justify-center w-16 h-16 text-sm text-gray-600 bg-gray-200 rounded-lg">
                                                    <img src="https://d9-wret.s3.us-west-2.amazonaws.com/assets/palladium/production/s3fs-public/thumbnails/image/file.jpg" alt="File Placeholder" class="object-cover w-full h-full rounded-lg">
                                                </div>
                                            @endif
                                            <span class="text-sm text-gray-700">{{ $shortDescription }}</span>
                                        </div>
                                    @endforeach
                                </div>
<!-- Input for Setting View Limit -->
<div class="mt-4 flex items-center">
    <label for="view_limit_{{ $chirp->id }}" class="block text-sm font-medium text-gray-700 mr-2">Set View Limit</label>
    <input type="number" id="view_limit_{{ $chirp->id }}" class="w-24 px-2 py-1 border-gray-300 rounded-md" value="1" min="0">
</div>

<div class="mt-4 flex items-center">
    <label for="expiration_time_{{ $chirp->id }}" class="block text-sm font-medium text-gray-700 mr-2">Expiration Time (minutes)</label>
    <input type="number" name="time_limit" id="expiration_time_{{ $chirp->id }}" class="w-24 px-2 py-1 border-gray-300 rounded-md" value="60" min="0">
</div>

                                <!-- Action Buttons -->
                                <div class="mt-4 flex space-x-2">
                                    <!-- Download All Files Button -->
                                    @if(count($files) > 1)
                                        <a href="{{ route('chirps.download', ['id' => $chirp->id]) }}" class="inline-block px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-700">
                                            Download All Files as ZIP
                                        </a>
                                    @endif

                                    <!-- Share Files Button -->
                                    <button class="share-file-btn inline-block px-4 py-2 text-white bg-green-500 rounded hover:bg-green-700" data-chirp-id="{{ $chirp->id }}" data-view-limit-input="#view_limit_{{ $chirp->id }}">
                                        Share Files
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <script>
        let removedFiles = []; // Array to track files to remove

        // JavaScript function to show previews of the selected files
        function previewFiles() {
            const fileInput = document.getElementById('files');
            const previewContainer = document.getElementById('file-preview');
            previewContainer.innerHTML = ''; // Clear any previous previews

            Array.from(fileInput.files).forEach(file => {
                const fileReader = new FileReader();

                // Preview image files
                fileReader.onload = function (e) {
                    const fileType = file.type.split('/')[0]; // Get file type (image, video, etc.)
                    let previewElement;

                    if (fileType === 'image') {
                        previewElement = document.createElement('div');
                        previewElement.classList.add('flex', 'flex-col', 'items-center');
                        previewElement.innerHTML = `
                            <div class="relative w-16 h-16 overflow-hidden rounded-lg">
                                <img src="${e.target.result}" alt="${file.name}" class="object-cover w-full h-full">
                            </div>
                            <span class="text-sm text-gray-700">${file.name.substring(0, 5)}.${file.name.split('.').pop()}</span>
                            <button type="button" class="text-5xl text-red-500 hover:text-red-700" onclick="removeFilePreview(event)">
                                ×
                            </button>
                        `;
                    } else {
                        previewElement = document.createElement('div');
                        previewElement.classList.add('flex', 'flex-col', 'items-center');
                        previewElement.innerHTML = `
                            <div class="flex items-center justify-center w-16 h-16 text-sm text-gray-600 bg-gray-200 rounded-lg">
                                <img src="https://d9-wret.s3.us-west-2.amazonaws.com/assets/palladium/production/s3fs-public/thumbnails/image/file.jpg" alt="File Placeholder" class="object-cover w-full h-full rounded-lg">
                            </div>
                            <span class="text-sm text-gray-700">${file.name.substring(0, 5)}.${file.name.split('.').pop()}</span>
                            <button type="button" class="text-5xl text-red-500 hover:text-red-700" onclick="removeFilePreview(event)">
                                ×
                            </button>
                        `;
                    }

                    previewContainer.appendChild(previewElement);
                };

                // Read the file
                fileReader.readAsDataURL(file);
            });
        }

        // JavaScript function to remove a file preview and remove it from the file input
        function removeFilePreview(event) {
            const previewElement = event.target.closest('div');
            previewElement.remove(); // Remove the preview element

            // Track the removed file in the removedFiles array
            const fileName = previewElement.querySelector('img') ? previewElement.querySelector('img').alt : null;
            if (fileName) {
                removedFiles.push(fileName);
            }

            // Filter out the removed files from the file input's list
            const fileInput = document.getElementById('files');
            const newFiles = Array.from(fileInput.files).filter(file => !removedFiles.includes(file.name));
            const dataTransfer = new DataTransfer();

            newFiles.forEach(file => dataTransfer.items.add(file));

            fileInput.files = dataTransfer.files; // Update the file input with the remaining files
        }

        // Submit the form and ensure only the valid files are included
        document.getElementById('chirp-form').addEventListener('submit', function (e) {
            const fileInput = document.getElementById('files');

            // Only include the files that were not removed
            const remainingFiles = Array.from(fileInput.files).filter(file => !removedFiles.includes(file.name));

            const dataTransfer = new DataTransfer();
            remainingFiles.forEach(file => dataTransfer.items.add(file));

            fileInput.files = dataTransfer.files; // Update the input with the remaining files
        });

        document.querySelectorAll('.share-file-btn').forEach(button => {
    button.addEventListener('click', function() {
        const chirpId = this.getAttribute('data-chirp-id');
        const viewLimitInput = this.getAttribute('data-view-limit-input');
        const viewLimit = document.querySelector(viewLimitInput).value;

        // Capture the time limit from the corresponding input field
        const timeLimitInput = document.getElementById(`expiration_time_${chirpId}`);
        const timeLimit = timeLimitInput ? timeLimitInput.value : 0; // Default to 0 if not found

        fetch(`/chirps/${chirpId}/share`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ view_limit: viewLimit, time_limit: timeLimit }), // Include time_limit
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.share_link) {
                // Copy the share link to the clipboard
                navigator.clipboard.writeText(data.share_link).then(() => {
                    // Change the button text to indicate success
                    button.textContent = 'Copied!';
                    button.classList.remove('bg-green-500', 'hover:bg-green-700');
                    button.classList.add('bg-gray-500', 'hover:bg-gray-700');
                });
            } else {
                button.textContent = 'Share Failed';
                setTimeout(() => {
                    button.textContent = 'Share Files';
                }, 2000); // Reset button text after 2 seconds
            }
        })
        .catch(error => {
            console.error('There was a problem with the fetch operation:', error);
            button.textContent = 'Error';
            setTimeout(() => {
                button.textContent = 'Share Files';
            }, 2000); // Reset button text after 2 seconds
        });
    });
});

    </script>
</x-app-layout>
