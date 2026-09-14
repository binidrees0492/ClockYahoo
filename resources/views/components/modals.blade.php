<div>
    <div id="subscribeToCalendarModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black bg-opacity-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">Subscribe to Calendar</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500">
                            Use this URL to link your personal calendars such as Google Calendar, Apple Calendar, Yahoo! Calendar or Outlook to your Schedule.
                        </p>
                        <div class="flex mt-4 space-x-2">
                            <input id="calendar-url" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500" value="https://calendar.link/example" readonly>
                            <button onclick="document.getElementById('calendar-url').select(); document.execCommand('copy');" class="px-4 py-2 font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">Copy</button>
                        </div>
                    </div>
                </div>
                <div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="document.getElementById('subscribeToCalendarModal').classList.add('hidden')" class="w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
