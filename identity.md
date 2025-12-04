<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Admin UI Identity — Expanded</title>
<style>
  body {
    margin: 0;
    font-family: Arial, sans-serif;
    transition: background 0.3s, color 0.3s;
  }

.theme-block { padding: 40px; }
.light-mode { background: #f5f5f7; color: #222; }
.dark-mode { background: #1a1a1c; color: #e8e8e8; }
h2 { margin-top: 0; }
h3 { margin-top: 30px; }

/_ Base styles _/
.btn, .input, .card, .menu, .menu a {
transition: all 0.25s ease;
border-radius: 8px;
}

/_ BUTTONS _/
.btn { display: inline-block; padding: 10px 20px; margin: 10px; font-size: 15px; cursor: pointer; text-decoration: none; }

/_ LIGHT BUTTONS 1,3,4,5,6,8 _/
.light .b1 { background:#fff; border:1px solid #d0d0d0; }
.light .b3 { background:#f0f0ff; border:1px solid #b8b8ff; }
.light .b4 { background:#e8f8ff; border:1px solid #9dd8ff; }
.light .b5 { background:#e5ffe8; border:1px solid #8fde94; }
.light .b6 { background:#fff4e0; border:1px solid #f4c98b; }
.light .b8 { background:#ececec; border:1px solid #bfbfbf; }

/_ LIGHT HOVER _/
.light .btn:hover { transform: translateY(-1px); box-shadow:0 3px 8px #0002; }

/_ DARK BUTTONS _/
.dark .b1 { background:#2a2a2d; border:1px solid #444; }
.dark .b3 { background:#2b2d40; border:1px solid #53567a; }
.dark .b4 { background:#1f2a33; border:1px solid #3e6d82; }
.dark .b5 { background:#1d3323; border:1px solid #3a8050; }
.dark .b6 { background:#3a2a14; border:1px solid #a87838; }
.dark .b8 { background:#2e2e2e; border:1px solid #595959; }

/_ DARK HOVER _/
.dark .btn:hover { transform: translateY(-1px); box-shadow:0 3px 8px #0007; }

/_ INPUT FIELDS _/
.input { padding:10px 14px; border-radius:6px; margin:10px 0; width:260px; border:1px solid; }
.light .input { background:#fff; border-color:#ccc; }
.dark .input { background:#2a2a2d; border-color:#555; color:#eee; }
.input:focus { outline:none; box-shadow:0 0 0 2px #6aa0ff66; }

/_ CARDS _/
.card { padding:20px; margin:15px 0; border-radius:10px; border:1px solid; }
.light .card { background:#fff; border-color:#ddd; }
.dark .card { background:#242427; border-color:#444; }

/_ MENU _/
.menu { display:flex; gap:10px; margin-bottom:20px; }
.menu a { padding:8px 14px; text-decoration:none; display:inline-block; border-radius:6px; }
.light .menu a { color:#222; background:#ffffff; border:1px solid #ccc; }
.dark .menu a { color:#eee; background:#2a2a2d; border:1px solid #555; }
.menu a:hover { transform:translateY(-1px); }

/_ ACTION BUTTON COLORS _/
.action-save { background:#4caf50; color:#fff; border:1px solid #3a8f3e; }
.action-save:hover { background:#44a047; }

.action-add { background:#2196f3; color:#fff; border:1px solid #1e88e5; }
.action-add:hover { background:#1e88e5; }

.action-delete { background:#f44336; color:#fff; border:1px solid #d32f2f; }
.action-delete:hover { background:#e53935; }

</style>
</head>
<body>

<div class="theme-block light-mode light">
  <h2>LIGHT THEME — Buttons</h2>
  <!-- Action Buttons -->
  <a class="btn action-save">Save</a>
  <a class="btn action-add">Add</a>
  <a class="btn action-delete">Delete</a>
  <br><br>
  <a class="btn b5">Button 5</a>

  <h3>Form Inputs</h3>
  <input class="input" placeholder="Text input" />

  <h3>Card Example</h3>
  <div class="card">This is a card block with padding and smooth edges.</div>

  <h3>Menu Example</h3>
  <div class="menu light">
    <a href="#">Dashboard</a>
    <a href="#">Pages</a>
    <a href="#">Settings</a>
  </div>
</div>

<div class="theme-block dark-mode dark">
  <h2>DARK THEME — Buttons</h2>
  <!-- Action Buttons -->
  <a class="btn action-save">Save</a>
  <a class="btn action-add">Add</a>
  <a class="btn action-delete">Delete</a>
  <br><br>
  <a class="btn b5">Button 5</a>

  <h3>Form Inputs</h3>
  <input class="input" placeholder="Text input" />

  <h3>Card Example</h3>
  <div class="card">This is a card block with padding and smooth edges.</div>

  <h3>Menu Example</h3>
  <div class="menu dark">
    <a href="#">Dashboard</a>
    <a href="#">Pages</a>
    <a href="#">Settings</a>
  </div>
</div>

</body>
</html>
