@extends('layouts.app')

@section('title', 'Import Data')

@section('content')
<div class="import-page">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'href' => route('app.dashboard')],
        ['label' => 'Import Data', 'current' => true],
    ]" />

    <div class="import-page__header">
        <h1 class="import-page__title">Import Data</h1>
        <p class="import-page__subtitle">Migrate your data from the original ChickenCare app</p>
    </div>

    {{-- Success Banner --}}
    @if(session('success'))
        <div class="import-page__banner import-page__banner--success" role="alert">
            <span>✅</span>
            <div>
                <strong>{{ session('success') }}</strong>
                @if(session('import_counts'))
                    <ul class="import-page__counts-list">
                        @foreach(session('import_counts') as $key => $count)
                            <li>{{ ucwords(str_replace('_', ' ', $key)) }}: <strong>{{ $count }}</strong> record{{ $count !== 1 ? 's' : '' }}</li>
                        @endforeach
                    </ul>
                @endif
                @if(session('import_errors') && count(session('import_errors')) > 0)
                    <div class="import-page__warnings">
                        <strong>⚠️ Notes:</strong>
                        <ul>
                            @foreach(session('import_errors') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="import-page__banner import-page__banner--error" role="alert">
            <span>❌</span>
            <div>
                <strong>Import failed</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="import-page__content">
        {{-- Instructions --}}
        <div class="form-card">
            <div class="form-card__header">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">📥</span>
                    <h2 class="form-card__title">How to Import</h2>
                </div>
                <p class="form-card__subtitle">Follow these steps to migrate your data</p>
            </div>

            <div class="import-page__steps">
                <div class="import-page__step">
                    <span class="import-page__step-number">1</span>
                    <div>
                        <h4 class="import-page__step-title">Export from Original App</h4>
                        <p class="import-page__step-desc">In the original ChickenCare app, go to <strong>Profile → Export Data</strong>, select <strong>"All Data"</strong> and <strong>"JSON"</strong> format, then click Export.</p>
                    </div>
                </div>
                <div class="import-page__step">
                    <span class="import-page__step-number">2</span>
                    <div>
                        <h4 class="import-page__step-title">Upload the JSON File</h4>
                        <p class="import-page__step-desc">Select the exported <code>.json</code> file below. The file should contain your egg entries, expenses, flock data, customers, and sales.</p>
                    </div>
                </div>
                <div class="import-page__step">
                    <span class="import-page__step-number">3</span>
                    <div>
                        <h4 class="import-page__step-title">Review & Confirm</h4>
                        <p class="import-page__step-desc">After upload, you'll see a summary of imported records. Duplicate runs will add data on top of existing records.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Supported Data --}}
        <div class="form-card">
            <div class="form-card__header">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">📋</span>
                    <h2 class="form-card__title">Supported Data</h2>
                </div>
                <p class="form-card__subtitle">The following data types will be imported</p>
            </div>

            <div class="import-page__data-grid">
                <div class="import-page__data-item">
                    <span class="import-page__data-icon">🥚</span>
                    <div>
                        <strong>Egg Entries</strong>
                        <p>Daily counts, sizes, colors, notes</p>
                    </div>
                </div>
                <div class="import-page__data-item">
                    <span class="import-page__data-icon">💰</span>
                    <div>
                        <strong>Expenses</strong>
                        <p>Categories, amounts, descriptions</p>
                    </div>
                </div>
                <div class="import-page__data-item">
                    <span class="import-page__data-icon">🌾</span>
                    <div>
                        <strong>Feed Inventory</strong>
                        <p>Brands, types, quantities, costs</p>
                    </div>
                </div>
                <div class="import-page__data-item">
                    <span class="import-page__data-icon">🐔</span>
                    <div>
                        <strong>Flock Profile & Batches</strong>
                        <p>Farm info, batches, events, death records</p>
                    </div>
                </div>
                <div class="import-page__data-item">
                    <span class="import-page__data-icon">👥</span>
                    <div>
                        <strong>Customers</strong>
                        <p>Names, phone numbers, notes</p>
                    </div>
                </div>
                <div class="import-page__data-item">
                    <span class="import-page__data-icon">📈</span>
                    <div>
                        <strong>Sales</strong>
                        <p>Transactions with customer links</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Upload Form --}}
        <div class="form-card" x-data="importForm()">
            <div class="form-card__header">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">📤</span>
                    <h2 class="form-card__title">Upload Export File</h2>
                </div>
                <p class="form-card__subtitle">Select your JSON export file (max 10 MB)</p>
            </div>

            <form action="{{ route('app.import.store') }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="form-card__form"
                  @submit="submitting = true">
                @csrf

                <div class="import-page__dropzone"
                     :class="{ 'import-page__dropzone--active': dragging, 'import-page__dropzone--has-file': fileName }"
                     @dragover.prevent="dragging = true"
                     @dragleave.prevent="dragging = false"
                     @drop.prevent="handleDrop($event)">
                    <div class="import-page__dropzone-content" x-show="!fileName">
                        <span class="import-page__dropzone-icon">📁</span>
                        <p class="import-page__dropzone-text">Drag & drop your JSON file here</p>
                        <p class="import-page__dropzone-subtext">or</p>
                        <label class="btn btn--secondary import-page__browse-btn">
                            Browse Files
                            <input type="file"
                                   name="import_file"
                                   accept=".json"
                                   class="sr-only"
                                   @change="handleFileSelect($event)"
                                   x-ref="fileInput" />
                        </label>
                    </div>
                    <div class="import-page__file-info" x-show="fileName" x-cloak>
                        <span class="import-page__file-icon">📄</span>
                        <div>
                            <p class="import-page__file-name" x-text="fileName"></p>
                            <p class="import-page__file-size" x-text="fileSize"></p>
                        </div>
                        <button type="button"
                                class="import-page__file-remove"
                                @click="clearFile()"
                                aria-label="Remove file">&times;</button>
                    </div>
                </div>

                {{-- Preview --}}
                <template x-if="preview">
                    <div class="import-page__preview">
                        <h4 class="import-page__preview-title">📊 File Preview</h4>
                        <div class="import-page__preview-grid">
                            <template x-for="item in preview" :key="item.key">
                                <div class="import-page__preview-item">
                                    <span class="import-page__preview-count" x-text="item.count"></span>
                                    <span class="import-page__preview-label" x-text="item.label"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <div class="import-page__warning-box">
                    <span>⚠️</span>
                    <p>Importing will <strong>add</strong> records to your existing data. It will not overwrite or delete existing records. Running import multiple times will create duplicate data.</p>
                </div>

                <button type="submit"
                        class="btn btn--primary btn--full"
                        :disabled="!fileName || submitting">
                    <template x-if="submitting">
                        <span class="btn__spinner"></span>
                    </template>
                    <span x-text="submitting ? 'Importing...' : '📥 Import Data'"></span>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.importForm = function() {
        return {
            fileName: null,
            fileSize: null,
            dragging: false,
            submitting: false,
            preview: null,

            handleFileSelect(event) {
                const file = event.target.files[0];
                if (file) this.processFile(file);
            },

            handleDrop(event) {
                this.dragging = false;
                const file = event.dataTransfer.files[0];
                if (file) {
                    this.processFile(file);
                    // Update the file input
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    this.$refs.fileInput.files = dt.files;
                }
            },

            processFile(file) {
                if (!file.name.endsWith('.json')) {
                    alert('Please select a JSON file.');
                    return;
                }

                this.fileName = file.name;
                this.fileSize = this.formatSize(file.size);
                this.preview = null;

                const reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        const data = JSON.parse(e.target.result);
                        this.generatePreview(data);
                    } catch {
                        this.preview = null;
                    }
                };
                reader.readAsText(file);
            },

            generatePreview(data) {
                const items = [];
                const sections = [
                    { key: 'eggEntries', label: 'Egg Entries' },
                    { key: 'expenses', label: 'Expenses' },
                    { key: 'feedInventory', label: 'Feed Records' },
                    { key: 'flockBatches', label: 'Flock Batches' },
                    { key: 'flockEvents', label: 'Flock Events' },
                    { key: 'deathRecords', label: 'Death Records' },
                    { key: 'customers', label: 'Customers' },
                    { key: 'sales', label: 'Sales' },
                ];

                for (const section of sections) {
                    const arr = data[section.key];
                    if (Array.isArray(arr) && arr.length > 0) {
                        items.push({ key: section.key, label: section.label, count: arr.length });
                    }
                }

                if (data.flockProfile) {
                    items.unshift({ key: 'flockProfile', label: 'Flock Profile', count: 1 });
                }

                this.preview = items.length > 0 ? items : null;
            },

            clearFile() {
                this.fileName = null;
                this.fileSize = null;
                this.preview = null;
                this.$refs.fileInput.value = '';
            },

            formatSize(bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
                return (bytes / 1048576).toFixed(1) + ' MB';
            }
        };
    };
</script>
@endpush
