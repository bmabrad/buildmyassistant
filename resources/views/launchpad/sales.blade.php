{{-- Placeholder sales page - to be built in Phase 5 --}}
<!DOCTYPE html>
<html>
<head><title>AI Assistant Launchpad</title></head>
<body>
    <h1>AI Assistant Launchpad</h1>
    <form method="POST" action="{{ route('launchpad.checkout') }}">
        @csrf
        <button type="submit">Buy now — $5 AUD</button>
    </form>
</body>
</html>
