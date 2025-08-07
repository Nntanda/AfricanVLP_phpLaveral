@props([
    'name' => 'files',
    'multiple' => true,
    'accept' => '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.gif,.webp',
    'maxSize' => '10MB',
    'existingFiles' => collect([])
])

<div class="file-upload-zone" x-data="fileUpload()" x-init="init()">
    <!-- Upload Area -->
    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors"
         :class="{ 'border-blue-400 bg-blue-50': isDragOver }"
         @dragover.prevent="isDragOver = true"
         @dragleave.prevent="isDragOver = false"
         @drop.prevent="handleDrop($event)">
        
        <div class="space-y-2">
            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400"></i>
            <div>
                <p class="text-lg font-medium text-gray-900">Drop files here or click to browse</p>
                <p class="text-sm text-gray-500">
                    Supports: PDF, Word, Excel, PowerPoint, Text, Images ({{ $maxSize }} max per file)
                </p>
            </div>
            
            <input type="file" 
                   name="{{ $name }}{{ $multiple ? '[]' : '' }}"
                   {{ $multiple ? 'multiple' : '' }}
                   accept="{{ $accept }}"
                   class="hidden"
                   x-ref="fileInput"
                   @change="handleFileSelect($event)">
            
            <button type="button" 
                    @click="$refs.fileInput.click()"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-plus mr-2"></i>
                Choose Files
            </button>
        </div>
    </div>

    <!-- Selected Files Preview -->
    <div x-show="selectedFiles.length > 0" class="mt-4">
        <h4 class="text-sm font-medium text-gray-900 mb-2">Selected Files:</h4>
        <div class="space-y-2">
            <template x-for="(file, index) in selectedFiles" :key="index">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-file text-gray-400"></i>
                        <div>
                            <p class="text-sm font-medium text-gray-900" x-text="file.name"></p>
                            <p class="text-xs text-gray-500" x-text="formatFileSize(file.size)"></p>
                        </div>
                    </div>
                    <button type="button" 
                            @click="removeFile(index)"
                            class="text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- Existing Files -->
    @if($existingFiles->count() > 0)
        <div class="mt-6">
            <h4 class="text-sm font-medium text-gray-900 mb-2">Existing Files:</h4>
            <div class="space-y-2">
                @foreach($existingFiles as $file)
                    <div class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg">
                        <div class="flex items-center space-x-3">
                            @if($file->isImage())
                                <img src="{{ $file->thumbnail_url }}" alt="{{ $file->original_name }}" class="w-10 h-10 object-cover rounded">
                            @else
                                <i class="{{ $file->getFileTypeIconUrl() }}"></i>
                            @endif
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $file->original_name }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $file->formatted_size }} • 
                                    {{ $file->download_count }} downloads
                                    @if($file->is_primary)
                                        • <span class="text-blue-600 font-medium">Primary</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('api.resource-files.download', $file) }}" 
                               class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-download"></i>
                            </a>
                            <button type="button" 
                                    onclick="deleteFile({{ $file->id }})"
                                    class="text-red-500 hover:text-red-700">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Error Messages -->
    <div x-show="errors.length > 0" class="mt-4">
        <div class="bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
                <i class="fas fa-exclamation-circle text-red-400 mr-2 mt-0.5"></i>
                <div>
                    <h3 class="text-sm font-medium text-red-800">Upload Errors:</h3>
                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                        <template x-for="error in errors">
                            <li x-text="error"></li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fileUpload() {
    return {
        selectedFiles: [],
        isDragOver: false,
        errors: [],
        maxFileSize: 10 * 1024 * 1024, // 10MB
        allowedTypes: ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'webp'],

        init() {
            // Initialize component
        },

        handleDrop(event) {
            this.isDragOver = false;
            const files = Array.from(event.dataTransfer.files);
            this.processFiles(files);
        },

        handleFileSelect(event) {
            const files = Array.from(event.target.files);
            this.processFiles(files);
        },

        processFiles(files) {
            this.errors = [];
            
            files.forEach(file => {
                if (this.validateFile(file)) {
                    this.selectedFiles.push(file);
                }
            });

            // Update the file input with selected files
            this.updateFileInput();
        },

        validateFile(file) {
            // Check file size
            if (file.size > this.maxFileSize) {
                this.errors.push(`${file.name}: File size exceeds 10MB limit`);
                return false;
            }

            // Check file type
            const extension = file.name.split('.').pop().toLowerCase();
            if (!this.allowedTypes.includes(extension)) {
                this.errors.push(`${file.name}: File type not allowed`);
                return false;
            }

            return true;
        },

        removeFile(index) {
            this.selectedFiles.splice(index, 1);
            this.updateFileInput();
        },

        updateFileInput() {
            // Create a new DataTransfer object to update the file input
            const dt = new DataTransfer();
            this.selectedFiles.forEach(file => {
                dt.items.add(file);
            });
            this.$refs.fileInput.files = dt.files;
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    }
}

function deleteFile(fileId) {
    if (confirm('Are you sure you want to delete this file?')) {
        fetch(`/api/resource-files/${fileId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to delete file: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the file');
        });
    }
}
</script>