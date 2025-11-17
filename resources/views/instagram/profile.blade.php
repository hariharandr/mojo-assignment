<!doctype html>
<html>
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Instagram Profile</title>
  <style>
    body{font-family:system-ui,Arial,Helvetica,sans-serif;padding:20px;background:#f7f7f7;color:#111}
    .card{background:white;padding:18px;border-radius:8px;max-width:900px;margin:20px auto;box-shadow:0 6px 20px rgba(0,0,0,0.06)}
    img.avatar{width:80px;height:80px;border-radius:8px;object-fit:cover}
    .grid{display:grid;grid-template-columns:1fr 2fr;gap:16px;align-items:center}
    .media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;margin-top:12px}
    .media img{width:100%;height:140px;object-fit:cover;border-radius:6px}
    a.btn{display:inline-block;padding:8px 12px;background:#111;color:#fff;border-radius:6px;text-decoration:none}
  </style>
</head>
<body>
  <div class="card">
    <h2>Instagram Profile</h2>

    @if($profile)
      <div class="grid">
        <div>
          @if(!empty($profile['profile_picture_url']))
            <img src="{{ $profile['profile_picture_url'] }}" alt="avatar" class="avatar">
          @endif
        </div>
        <div>
          <p><strong>Username:</strong> {{ $profile['username'] ?? ($profile['user_id'] ?? 'N/A') }}</p>
          <p><strong>Account Type:</strong> {{ $profile['account_type'] ?? 'N/A' }}</p>
          <p><strong>Media Count:</strong> {{ $profile['media_count'] ?? 'N/A' }}</p>
        </div>
      </div>
    @else
      <p>No profile data available.</p>
    @endif

    <h3 style="margin-top:18px">Recent media</h3>
    <div class="media-grid">
      @forelse($media as $m)
        <div class="media">
          @if(!empty($m['media_url']))
            <a href="{{ $m['permalink'] ?? '#' }}" target="_blank">
              <img src="{{ $m['media_url'] }}" alt="media">
            </a>
          @else
            <div style="height:140px;background:#eee;border-radius:6px"></div>
          @endif
          <p style="font-size:13px">{{ \Illuminate\Support\Str::limit($m['caption'] ?? '', 80) }}</p>
        </div>
      @empty
        <p>No media found.</p>
      @endforelse
    </div>

    <div style="margin-top:18px">
      <a href="/" class="btn">Back to Home</a>
    </div>
  </div>
</body>
</html>
