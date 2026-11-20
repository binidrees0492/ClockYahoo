<div class="space-y-4">
    {{-- Header Banner & Mailbox Connection Status --}}
    <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-xl border border-gray-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-sky-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-gray-900 leading-tight">Webmail Client</h2>
                    <p class="text-xs text-gray-500">Manage, read, compose, and archive emails using your personal or server mailbox.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($user->use_custom_email && $user->imap_host)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>IMAP: {{ $user->imap_username ?: $user->email }} ({{ $user->imap_host }})</span>
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                    <i class="fa-solid fa-server text-slate-500 text-[11px]"></i>
                    <span>Default System Mailer ({{ $user->email }})</span>
                </span>
            @endif

            <button type="button" wire:click="openSettings"
                    class="px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-xs font-semibold transition inline-flex items-center gap-1.5 shadow-2xs">
                <i class="fa-solid fa-gear text-gray-500"></i> Settings
            </button>

            <button wire:click="syncMail"
                    class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold transition inline-flex items-center gap-1.5">
                <i class="fa-solid fa-rotate"></i> Sync
            </button>

            <button wire:click="openCompose"
                    class="px-3.5 py-1.5 rounded-lg bg-[#005a9c] hover:bg-blue-800 text-white text-xs font-bold shadow-xs transition inline-flex items-center gap-1.5">
                <i class="fa-solid fa-pen-to-square"></i> Compose
            </button>
        </div>
    </div>

    {{-- Toast Message --}}
    @if($toastMessage)
        <div class="p-3 rounded-xl text-xs font-semibold flex items-center justify-between shadow-xs {{ $toastType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' }}">
            <span>{{ $toastType === 'success' ? '✓' : '⚠️' }} {{ $toastMessage }}</span>
            <button wire:click="dismissToast" class="text-gray-400 hover:text-gray-700 font-bold text-sm">&times;</button>
        </div>
    @endif

    {{-- Main Webmail Layout: Sidebar + List --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        {{-- Sidebar: Folders --}}
        <div class="md:col-span-1 space-y-3">
            <div class="bg-white rounded-xl border border-gray-200 p-3 shadow-xs space-y-1">
                <button wire:click="setFolder('inbox')"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold transition {{ $activeFolder === 'inbox' ? 'bg-sky-50 text-sky-800 font-bold border border-sky-200' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="inline-flex items-center gap-2">
                        <i class="fa-solid fa-inbox text-sky-600"></i> Inbox
                    </span>
                    @if($unreadCount > 0)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-600 text-white">{{ $unreadCount }}</span>
                    @endif
                </button>

                <button wire:click="setFolder('sent')"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold transition {{ $activeFolder === 'sent' ? 'bg-sky-50 text-sky-800 font-bold border border-sky-200' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="inline-flex items-center gap-2">
                        <i class="fa-solid fa-paper-plane text-emerald-600"></i> Sent
                    </span>
                    @if($sentCount > 0)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700">{{ $sentCount }}</span>
                    @endif
                </button>

                <button wire:click="setFolder('drafts')"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold transition {{ $activeFolder === 'drafts' ? 'bg-sky-50 text-sky-800 font-bold border border-sky-200' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="inline-flex items-center gap-2">
                        <i class="fa-solid fa-file text-amber-500"></i> Drafts
                    </span>
                </button>

                <button wire:click="setFolder('archived')"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold transition {{ $activeFolder === 'archived' ? 'bg-sky-50 text-sky-800 font-bold border border-sky-200' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="inline-flex items-center gap-2">
                        <i class="fa-solid fa-box-archive text-indigo-600"></i> Archived
                    </span>
                    @if($archivedCount > 0)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800">{{ $archivedCount }}</span>
                    @endif
                </button>

                <button wire:click="setFolder('trash')"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold transition {{ $activeFolder === 'trash' ? 'bg-sky-50 text-sky-800 font-bold border border-sky-200' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="inline-flex items-center gap-2">
                        <i class="fa-solid fa-trash-can text-rose-500"></i> Trash
                    </span>
                </button>
            </div>

            {{-- Configuration Quick Tip --}}
            <div class="bg-blue-50/70 border border-blue-200 rounded-xl p-3 text-xs text-blue-900 space-y-1">
                <div class="font-bold flex items-center justify-between">
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-circle-info text-blue-600"></i> Mailbox Config</span>
                    <button wire:click="openSettings" class="text-[11px] underline font-semibold text-blue-700 hover:text-blue-900">Manage</button>
                </div>
                <p class="text-[11px] text-blue-800 leading-normal">
                    @if($user->use_custom_email)
                        Using custom email <strong>{{ $user->smtp_from_address ?: $user->email }}</strong>. Outgoing mail connects to <strong>{{ $user->smtp_host ?: 'smtp' }}</strong>.
                    @else
                        Currently using system default mail server. Configure your personal Gmail, Outlook, or SMTP/IMAP directly here via Settings.
                    @endif
                </p>
            </div>
        </div>

        {{-- Email List & Viewer --}}
        <div class="md:col-span-3 space-y-3">
            {{-- Search & Controls --}}
            <div class="bg-white rounded-xl border border-gray-200 p-3 shadow-xs flex items-center justify-between gap-3">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search emails by subject, recipient, body..."
                           class="w-full pl-8 pr-3 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:ring-1 focus:ring-sky-500 focus:border-sky-500">
                </div>
                <div class="text-xs text-gray-500 font-medium">
                    Folder: <strong class="capitalize text-gray-800">{{ $activeFolder }}</strong>
                </div>
            </div>

            {{-- Emails Table / List --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-left text-gray-600 font-semibold">
                                <th class="p-3 w-44">{{ $activeFolder === 'sent' ? 'Recipient' : 'Sender' }}</th>
                                <th class="p-3">Subject & Preview</th>
                                <th class="p-3 text-center w-24">Status</th>
                                <th class="p-3 text-right w-28">Date</th>
                                <th class="p-3 text-right w-20">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($emails as $email)
                                <tr class="hover:bg-sky-50/50 transition {{ $selectedEmailId === $email->id ? 'bg-sky-50 border-l-4 border-l-[#005a9c]' : '' }}">
                                    <td wire:click="viewEmail({{ $email->id }})" class="p-3 font-semibold text-gray-800 truncate max-w-[170px] cursor-pointer" title="{{ $activeFolder === 'sent' ? $email->recipient : ($email->sender ?: 'System') }}">
                                        {{ $activeFolder === 'sent' ? $email->recipient : ($email->sender ?: 'System') }}
                                        @if(!empty($email->attachments))
                                            <i class="fa-solid fa-paperclip text-gray-400 ml-1" title="Has attachments"></i>
                                        @endif
                                    </td>
                                    <td wire:click="viewEmail({{ $email->id }})" class="p-3 cursor-pointer">
                                        <div class="font-medium text-gray-900 truncate max-w-md {{ !$email->read_at && $activeFolder === 'inbox' ? 'font-bold' : '' }}">
                                            {{ $email->subject }}
                                        </div>
                                        <div class="text-[11px] text-gray-500 truncate max-w-md">
                                            {{ Str::limit(strip_tags($email->body), 75) }}
                                        </div>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold
                                            {{ $email->status === 'sent' ? 'bg-emerald-100 text-emerald-800' : ($email->status === 'failed' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                                            {{ ucfirst($email->status) }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-right text-gray-500 whitespace-nowrap text-[11px]">
                                        {{ $email->created_at->setTimezone(auth()->user()->getTimezone())->format('M d, H:i') }}
                                    </td>
                                    <td class="p-3 text-right whitespace-nowrap">
                                        @if($email->is_archived)
                                            <button wire:click="unarchiveEmail({{ $email->id }})" class="text-indigo-600 hover:text-indigo-800 font-semibold text-[11px] p-1" title="Restore to Inbox">
                                                <i class="fa-solid fa-arrow-rotate-left"></i>
                                            </button>
                                        @else
                                            <button wire:click="archiveEmail({{ $email->id }})" class="text-gray-400 hover:text-indigo-600 font-semibold text-[11px] p-1" title="Archive">
                                                <i class="fa-solid fa-box-archive"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-gray-400 italic">
                                        No messages found in {{ $activeFolder }}. Click "Compose" to write an email.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($emails->hasPages())
                    <div class="p-3 border-t border-gray-100">
                        {{ $emails->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- VIEW EMAIL DETAIL MODAL --}}
    @if($selectedEmail)
        <div class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6 space-y-4 max-h-[90vh] flex flex-col">
                <div class="flex justify-between items-start border-b border-gray-200 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 leading-snug">{{ $selectedEmail->subject }}</h3>
                        <div class="text-xs text-gray-500 mt-1 space-x-2">
                            <span>From: <strong>{{ $selectedEmail->sender ?: 'System' }}</strong></span>
                            <span>&bull;</span>
                            <span>To: <strong>{{ $selectedEmail->recipient }}</strong></span>
                            <span>&bull;</span>
                            <span>{{ $selectedEmail->created_at->setTimezone(auth()->user()->getTimezone())->format('M d, Y H:i') }}</span>
                            @if($selectedEmail->is_archived)
                                <span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold text-[10px]">Archived</span>
                            @endif
                        </div>
                    </div>
                    <button wire:click="closeEmailDetail" class="text-gray-400 hover:text-gray-700 text-2xl font-bold leading-none">&times;</button>
                </div>

                <div class="flex-1 overflow-y-auto bg-gray-50 p-4 rounded-lg border border-gray-200 text-xs text-gray-800 leading-relaxed whitespace-pre-wrap">
                    {!! $selectedEmail->body ?: 'No content.' !!}
                </div>

                {{-- Attachments display --}}
                @if(!empty($selectedEmail->attachments))
                    <div class="border border-gray-200 rounded-lg p-3 bg-white space-y-2">
                        <span class="text-xs font-bold text-gray-700 flex items-center gap-1.5">
                            <i class="fa-solid fa-paperclip text-sky-600"></i> Attachments ({{ count($selectedEmail->attachments) }})
                        </span>
                        <div class="flex flex-wrap gap-2">
                            @foreach($selectedEmail->attachments as $att)
                                @php
                                    $attName = is_array($att) ? ($att['name'] ?? 'attachment') : basename($att);
                                    $attUrl = is_array($att) ? ($att['url'] ?? null) : null;
                                @endphp
                                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 border border-gray-300 rounded-lg text-xs text-gray-700">
                                    <i class="fa-regular fa-file"></i>
                                    <span class="font-medium truncate max-w-xs">{{ $attName }}</span>
                                    @if($attUrl)
                                        <a href="{{ $attUrl }}" download="{{ $attName }}" target="_blank" class="text-sky-600 hover:text-sky-800 ml-1 font-bold" title="Download">
                                            <i class="fa-solid fa-download"></i>
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($selectedEmail->error_message)
                    <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-800">
                        <strong>Error info:</strong> {{ $selectedEmail->error_message }}
                    </div>
                @endif

                <div class="flex justify-between items-center pt-2">
                    <div class="flex items-center gap-2">
                        @if($selectedEmail->is_archived)
                            <button wire:click="unarchiveEmail({{ $selectedEmail->id }})"
                                    class="px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5">
                                <i class="fa-solid fa-arrow-rotate-left"></i> Restore from Archive
                            </button>
                        @else
                            <button wire:click="archiveEmail({{ $selectedEmail->id }})"
                                    class="px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5">
                                <i class="fa-solid fa-box-archive"></i> Move to Archive
                            </button>
                        @endif

                        <button wire:click="deleteEmail({{ $selectedEmail->id }})"
                                class="px-3 py-1.5 bg-rose-50 text-rose-700 hover:bg-rose-100 rounded-lg text-xs font-semibold transition">
                            <i class="fa-solid fa-trash-can"></i> Delete
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <button wire:click="openCompose('{{ $selectedEmail->sender ?: $selectedEmail->recipient }}', '{{ $selectedEmail->subject }}')"
                                class="px-3.5 py-1.5 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-semibold transition">
                            <i class="fa-solid fa-reply"></i> Reply
                        </button>
                        <button wire:click="closeEmailDetail"
                                class="px-4 py-1.5 bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-lg text-xs font-semibold transition">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- COMPOSE EMAIL MODAL --}}
    @if($showCompose)
        <div class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 space-y-4 max-h-[92vh] flex flex-col">
                <div class="flex justify-between items-center border-b border-gray-200 pb-3">
                    <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                        <i class="fa-solid fa-pen-to-square text-sky-600"></i> Compose New Email
                    </h3>
                    <button wire:click="closeCompose" class="text-gray-400 hover:text-gray-700 text-2xl font-bold leading-none">&times;</button>
                </div>

                <form wire:submit.prevent="sendEmail" class="space-y-3 flex-1 overflow-y-auto">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">To <span class="text-red-500">*</span></label>
                        <input type="email" wire:model="composeTo" placeholder="recipient@example.com"
                               class="w-full p-2 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-sky-500">
                        @error('composeTo') <p class="text-red-500 text-[11px] mt-0.5">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Subject <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="composeSubject" placeholder="Email subject..."
                               class="w-full p-2 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-sky-500">
                        @error('composeSubject') <p class="text-red-500 text-[11px] mt-0.5">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Message <span class="text-red-500">*</span></label>
                        <textarea wire:model="composeBody" rows="5" placeholder="Type your email message here..."
                                  class="w-full p-2 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-sky-500"></textarea>
                        @error('composeBody') <p class="text-red-500 text-[11px] mt-0.5">{{ $message }}</p> @enderror
                    </div>

                    {{-- Attachments file input --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1 flex items-center justify-between">
                            <span><i class="fa-solid fa-paperclip text-gray-500"></i> Attachments</span>
                            <span class="text-[10px] text-gray-400">Max 10MB per file</span>
                        </label>
                        <input type="file" wire:model="attachments" multiple
                               class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 cursor-pointer">
                        <div wire:loading wire:target="attachments" class="text-[11px] text-sky-600 mt-1">
                            Uploading attachment(s)...
                        </div>

                        @if(!empty($attachments))
                            <div class="mt-2 space-y-1">
                                @foreach($attachments as $idx => $att)
                                    <div class="flex items-center justify-between bg-gray-50 px-2.5 py-1 rounded border border-gray-200 text-xs">
                                        <span class="truncate max-w-xs text-gray-700">{{ $att->getClientOriginalName() }} ({{ round($att->getSize() / 1024, 1) }} KB)</span>
                                        <button type="button" wire:click="removeAttachment({{ $idx }})" class="text-rose-500 hover:text-rose-700 font-bold">&times;</button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @error('attachments.*') <p class="text-red-500 text-[11px] mt-0.5">{{ $message }}</p> @enderror
                    </div>

                    <div class="text-[11px] text-gray-400">
                        Sending as: <strong>{{ $user->use_custom_email ? ($user->smtp_from_address ?: $user->email) : $user->email }}</strong>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" wire:click="closeCompose" class="px-4 py-2 border rounded-lg text-xs text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                                class="px-4 py-2 bg-[#005a9c] hover:bg-blue-800 text-white rounded-lg text-xs font-bold shadow-xs flex items-center gap-1.5 disabled:opacity-50">
                            <span wire:loading.remove wire:target="sendEmail"><i class="fa-solid fa-paper-plane mr-1"></i> Send Verified Email</span>
                            <span wire:loading wire:target="sendEmail"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Verifying & Sending...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- WEBMAIL SETTINGS MODAL --}}
    @if($showSettings)
        <div class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6 space-y-4 max-h-[92vh] flex flex-col">
                <div class="flex justify-between items-center border-b border-gray-200 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-sky-100 text-sky-800 flex items-center justify-center font-bold text-xs">
                            <i class="fa-solid fa-gear"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">Mailbox Server Settings</h3>
                            <p class="text-[11px] text-gray-500">Configure personal SMTP and IMAP connection settings for Webmail.</p>
                        </div>
                    </div>
                    <button wire:click="closeSettings" class="text-gray-400 hover:text-gray-700 text-2xl font-bold leading-none">&times;</button>
                </div>

                {{-- Settings form --}}
                <div class="flex-1 overflow-y-auto space-y-4 pr-1 text-xs">
                    {{-- Mailer Switch --}}
                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <label class="flex items-center gap-2.5 cursor-pointer font-bold text-gray-800">
                            <input type="checkbox" wire:model.live="useCustomEmail" class="rounded text-sky-600 focus:ring-sky-500">
                            <span>Use Custom Personal Mail Server (SMTP / IMAP)</span>
                        </label>
                        <p class="text-[11px] text-gray-500 mt-1 pl-6">
                            When unchecked, Webmail sends and syncs via the system default company mailer.
                        </p>
                    </div>

                    @if($settingsTestMessage)
                        <div class="p-3 rounded-lg text-xs font-semibold {{ $settingsTestStatus === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' }}">
                            {{ $settingsTestMessage }}
                        </div>
                    @endif

                    @if($useCustomEmail)
                        {{-- SMTP Section --}}
                        <div class="border border-sky-100 bg-sky-50/30 rounded-xl p-4 space-y-3">
                            <div class="flex items-center justify-between border-b border-sky-100 pb-2">
                                <span class="font-bold text-sky-900 flex items-center gap-1.5">
                                    <i class="fa-solid fa-paper-plane text-sky-600"></i> Outgoing Mail (SMTP)
                                </span>
                                <button type="button" wire:click="testSmtpSettings"
                                        class="px-2.5 py-1 bg-white border border-sky-300 text-sky-700 hover:bg-sky-50 rounded text-[11px] font-bold shadow-2xs">
                                    Test SMTP Connection
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="sm:col-span-2">
                                    <label class="block font-semibold text-gray-700 mb-1">SMTP Host</label>
                                    <input type="text" wire:model="smtpHost" placeholder="smtp.gmail.com" class="w-full p-2 border rounded text-xs bg-white">
                                </div>
                                <div>
                                    <label class="block font-semibold text-gray-700 mb-1">Port</label>
                                    <input type="number" wire:model="smtpPort" placeholder="587" class="w-full p-2 border rounded text-xs bg-white">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block font-semibold text-gray-700 mb-1">Username / Email</label>
                                    <input type="text" wire:model="smtpUsername" placeholder="user@domain.com" class="w-full p-2 border rounded text-xs bg-white">
                                </div>
                                <div>
                                    <label class="block font-semibold text-gray-700 mb-1">Password / App Pass</label>
                                    <input type="password" wire:model="smtpPassword" placeholder="••••••••" class="w-full p-2 border rounded text-xs bg-white">
                                </div>
                                <div>
                                    <label class="block font-semibold text-gray-700 mb-1">Encryption</label>
                                    <select wire:model="smtpEncryption" class="w-full p-2 border rounded text-xs bg-white">
                                        <option value="tls">TLS</option>
                                        <option value="ssl">SSL</option>
                                        <option value="null">None</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-semibold text-gray-700 mb-1">From Email Address</label>
                                    <input type="email" wire:model="smtpFromAddress" placeholder="{{ $user->email }}" class="w-full p-2 border rounded text-xs bg-white">
                                </div>
                                <div>
                                    <label class="block font-semibold text-gray-700 mb-1">From Name</label>
                                    <input type="text" wire:model="smtpFromName" placeholder="{{ $user->name }}" class="w-full p-2 border rounded text-xs bg-white">
                                </div>
                            </div>
                        </div>

                        {{-- IMAP Section --}}
                        <div class="border border-indigo-100 bg-indigo-50/30 rounded-xl p-4 space-y-3">
                            <div class="flex items-center justify-between border-b border-indigo-100 pb-2">
                                <span class="font-bold text-indigo-900 flex items-center gap-1.5">
                                    <i class="fa-solid fa-inbox text-indigo-600"></i> Incoming Mail (IMAP Sync)
                                </span>
                                <button type="button" wire:click="testImapSettings"
                                        class="px-2.5 py-1 bg-white border border-indigo-300 text-indigo-700 hover:bg-indigo-50 rounded text-[11px] font-bold shadow-2xs">
                                    Test IMAP Connection
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="sm:col-span-2">
                                    <label class="block font-semibold text-gray-700 mb-1">IMAP Host</label>
                                    <input type="text" wire:model="imapHost" placeholder="imap.gmail.com" class="w-full p-2 border rounded text-xs bg-white">
                                </div>
                                <div>
                                    <label class="block font-semibold text-gray-700 mb-1">Port</label>
                                    <input type="number" wire:model="imapPort" placeholder="993" class="w-full p-2 border rounded text-xs bg-white">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block font-semibold text-gray-700 mb-1">Username</label>
                                    <input type="text" wire:model="imapUsername" placeholder="user@domain.com" class="w-full p-2 border rounded text-xs bg-white">
                                </div>
                                <div>
                                    <label class="block font-semibold text-gray-700 mb-1">Password</label>
                                    <input type="password" wire:model="imapPassword" placeholder="••••••••" class="w-full p-2 border rounded text-xs bg-white">
                                </div>
                                <div>
                                    <label class="block font-semibold text-gray-700 mb-1">Encryption</label>
                                    <select wire:model="imapEncryption" class="w-full p-2 border rounded text-xs bg-white">
                                        <option value="ssl">SSL</option>
                                        <option value="tls">TLS</option>
                                        <option value="null">None</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                    <button type="button" wire:click="closeSettings" class="px-4 py-2 border rounded-lg text-xs text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="button" wire:click="saveMailboxSettings" class="px-5 py-2 bg-[#005a9c] hover:bg-blue-800 text-white rounded-lg text-xs font-bold shadow-xs">
                        Save Mailbox Settings
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
