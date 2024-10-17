<x-app-layout>
    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
        <form method="POST" action="{{ route('chirps.store') }}" enctype="multipart/form-data" id="chirp-form">
            @csrf
            <textarea name="message"
                placeholder="{{ __('What\'s on your mind?') }}"
                class="block w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm">{{ old('message') }}</textarea>
            <x-input-error :messages="$errors->get('message')" class="mt-2" />
            
            <!-- File Upload Section -->
            <div class="mb-4">
                <label for="files" class="block text-sm font-medium text-gray-700">Choose Files</label>
                <input type="file" name="files[]" id="files" class="mt-2 w-full px-4 py-2 rounded" multiple onchange="previewFiles()">
            </div>
            <x-input-error :messages="$errors->get('files')" class="mt-2" />

            <!-- Preview Area for Files -->
            <div id="file-preview" class="mt-4 flex flex-col gap-4"></div>

            <x-primary-button class="mt-4">{{ __('Chirp') }}</x-primary-button>
        </form>

        <div class="mt-6 bg-white shadow-sm rounded-lg divide-y">
            @foreach($chirps as $chirp)
                <div class="p-6 flex space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600 -scale-x-100" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>

                    <div class="flex-1">
                        <div class="flex justify-between items-center">
                            <div>
                                <span class="text-gray-800">{{ $chirp->user->name }}</span>
                                <small class="ml-2 text-sm text-gray-600">{{ $chirp->created_at->format('j M Y, g:i a') }}</small>
                            </div>
                        </div>

                        <p class="mt-4 text-lg text-gray-900">{{ $chirp->message }}</p>

                        @if($chirp->files)
                            <div class="mt-4">
                                <h3 class="text-sm text-gray-700 mb-2 bg-gray-100">Uploaded Files:</h3>
                                <div class="flex flex-col gap-4">
                                    @php
                                        // Check if chirp->files is an array or a JSON string
                                        $files = is_array($chirp->files) ? $chirp->files : json_decode($chirp->files, true);
                                    @endphp

                                    @foreach($files as $file)
                                        @php
                                            $isImage = in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif']);
                                        @endphp

                                        <div class="flex items-center justify-between space-x-2">
                                            <div class="flex items-center space-x-2">
                                                @if($isImage)
                                                    <a href="{{ route('chirps.image', $chirp->id) }}" target="_blank" class="block">
                                                        <img src="{{ route('chirps.image', $chirp->id) }}" alt="Chirp Image" class="w-16 h-16 object-cover rounded-lg">
                                                    </a>
                                                @else
                                                    <!-- Placeholder for non-image files -->
                                                    <div class="w-16 h-16 bg-gray-200 flex justify-center items-center text-sm text-gray-600 rounded-lg">
                                                        <img src="https://d9-wret.s3.us-west-2.amazonaws.com/assets/palladium/production/s3fs-public/thumbnails/image/file.jpg" alt="File Placeholder" class="object-cover w-full h-full rounded-lg">
                                                    </div>
                                                @endif
                                                <span class="text-sm text-gray-700">{{ basename($file) }}</span>
                                            </div>

                                            <!-- Download Button -->
                                            <a href="{{ route('chirps.download', ['id' => $chirp->id]) }}" class="text-gray-400 hover:text-gray-800">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 p-1 w-12 mr-8 bg-gray-200 rounded-lg" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 3v12.75m0 0l-3-3m3 3l3-3m5 5H4" />
                                                </svg>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Show ZIP download link if there are multiple files -->
                                @if(count($files) > 1)
                                    <a href="{{ route('chirps.download', ['id' => $chirp->id]) }}" class="inline-block mt-4 px-4 py-2 text-white bg-blue-500 hover:bg-blue-700 rounded">
                                        Download All Files as ZIP
                                    </a>
                                @endif
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
                        previewElement.classList.add('flex', 'items-center', 'justify-between', 'space-x-2');
                        previewElement.innerHTML = `
                            <div class="flex items-center space-x-2">
                                <div class="relative w-16 h-16 rounded-lg overflow-hidden">
                                    <img src="${e.target.result}" alt="${file.name}" class="object-cover w-full h-full">
                                </div>
                                <span class="text-sm text-gray-700">${file.name}</span>
                            </div>
                            <button type="button" class="text-red-500 hover:text-red-700 text-5xl" onclick="removeFilePreview(event)">
                                ×
                            </button>
                        `;
                    } else {
                        previewElement = document.createElement('div');
                        previewElement.classList.add('flex', 'items-center', 'justify-between', 'space-x-2');
                        previewElement.innerHTML = `
                            <div class="w-16 h-16 bg-gray-200 flex justify-center items-center text-sm text-gray-600 rounded-lg">
                                <img src="https://d9-wret.s3.us-west-2.amazonaws.com/assets/palladium/production/s3fs-public/thumbnails/image/file.jpg" alt="File Placeholder" class="object-cover w-full h-full rounded-lg">
                            </div>
                            <span class="text-sm text-gray-700">${file.name}</span>
                            <button type="button" class="text-red-500 hover:text-red-700 text-5xl" onclick="removeFilePreview(event)">
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
    </script>
</x-app-layout>
