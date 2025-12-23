<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Generation Status</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-96 text-center">
        <h1 class="text-2xl font-bold mb-4">PDF Generation</h1>

        <div id="status-container">
            <div id="processing" class="flex flex-col items-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mb-4"></div>
                <p class="text-gray-600">Processing... Please wait.</p>
            </div>

            <div id="completed" class="hidden">
                <div class="text-green-500 mb-4">
                    <svg class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <p class="text-gray-600 mb-4">Generation Completed!</p>
                <a id="download-link" href="#"
                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">Download PDF</a>
            </div>

            <div id="failed" class="hidden">
                <div class="text-red-500 mb-4">
                    <svg class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <p class="text-gray-600">Generation Failed. Please try again.</p>
            </div>
        </div>
    </div>

    <script>
        const jobId = "{{ $jobId }}";
        const checkStatusUrl = "{{ route('pdf.check-status', ['id' => ':id']) }}".replace(':id', jobId);
        const downloadUrl = "{{ route('pdf.download', ['id' => ':id']) }}".replace(':id', jobId);

        function checkStatus() {
            fetch(checkStatusUrl)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'completed') {
                        document.getElementById('processing').classList.add('hidden');
                        document.getElementById('completed').classList.remove('hidden');
                        document.getElementById('download-link').href = downloadUrl;
                    } else if (data.status === 'failed') {
                        document.getElementById('processing').classList.add('hidden');
                        document.getElementById('failed').classList.remove('hidden');
                    } else {
                        // Still processing, poll again
                        setTimeout(checkStatus, 2000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Check again in case of network glitch
                    setTimeout(checkStatus, 2000);
                });
        }

        // Start polling
        checkStatus();
    </script>
</body>

</html>