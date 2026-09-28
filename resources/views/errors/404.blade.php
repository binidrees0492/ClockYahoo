<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <style>
        :root {
            --bg-color: #0b0f19;
            --text-main: #ffffff;
            --text-muted: #94a3b8;
            --accent-glow: rgba(99, 102, 241, 0.15);
            --accent-primary: #6366f1;
            --accent-hover: #4f46e5;
            --card-bg: rgba(30, 41, 59, 0.5);
            --border-color: rgba(255, 255, 255, 0.08);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow-x: hidden;
            padding: 20px;
        }

        /* Subtle glowing background ambient spots */
        .ambient-glow {
            position: absolute;
            width: 500px;
            height: 500px;
            background: var(--accent-glow);
            filter: blur(120px);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1;
            pointer-events: none;
        }

        .container {
            max-width: 560px;
            width: 100%;
            text-align: center;
            position: relative;
            z-index: 2;
            background: var(--card-bg);
            padding: 3rem 2rem;
            border-radius: 24px;
            border: 1px solid var(--border-color);
            backdrop-filter: blur(16px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .error-code {
            font-size: 8rem;
            font-weight: 900;
            line-height: 1;
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            letter-spacing: -2px;
            position: relative;
            display: inline-block;
            animation: float 4s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-main);
        }

        p {
            color: var(--text-muted);
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2.5rem;
        }

        /* Built-in Search Bar */
        .search-box {
            display: flex;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 2rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .search-box:focus-within {
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        .search-box input {
            flex: 1;
            background: transparent;
            border: none;
            padding: 12px 16px;
            color: var(--text-main);
            font-size: 1rem;
            outline: none;
        }

        .search-box input::placeholder {
            color: #64748b;
        }

        .search-box button {
            background: var(--accent-primary);
            color: white;
            border: none;
            padding: 0 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .search-box button:hover {
            background: var(--accent-hover);
        }

        /* Action Buttons */
        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
        }

        .btn-primary {
            background-color: var(--accent-primary);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }

        .btn-primary:hover {
            background-color: var(--accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35);
        }

        .btn-secondary {
            background-color: transparent;
            color: var(--text-main);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.2);
        }

        @media (max-width: 480px) {
            .error-code { font-size: 6rem; }
            h1 { font-size: 1.6rem; }
            .actions { flex-direction: column; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>

<div class="ambient-glow"></div>

<div class="container">
    <div class="error-code">404</div>
    <h1>Lost in the deep web?</h1>
    <p>We can't seem to find the page you're looking for. It might have been moved, deleted, or never existed in the first place.</p>

    <!-- Optional Search Feature -->
    <form action="/search" method="GET" class="search-box">
        <input type="text" name="q" placeholder="Search our website..." required>
        <button type="submit">Search</button>
    </form>

    <!-- Navigation out of the dead-end -->
    <div class="actions">
        <a href="/" class="btn btn-primary">Go to Homepage</a>
        <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
    </div>
</div>

</body>
</html>
