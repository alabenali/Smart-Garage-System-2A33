<style>
.input-wrap { position:relative !important; }
.pw-eye {
    position:absolute; right:12px; top:50%; transform:translateY(-50%);
    background:none; border:none; color:#888; cursor:pointer;
    font-size:0.9rem; padding:0; line-height:1; z-index:3;
    transition:color 0.2s;
}
.pw-eye:hover { color:#00E5FF; }
body.light-mode .pw-eye { color:#94a3b8; }
body.light-mode .pw-eye:hover { color:#0284c7; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[type="password"]').forEach(function(input) {
        var parent = input.parentElement;
        // Add padding so text doesn't go under the eye
        input.style.paddingRight = '38px';
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'pw-eye';
        btn.title = 'Afficher / masquer';
        btn.innerHTML = '<i class="fas fa-eye"></i>';
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.innerHTML = show ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
        });
        parent.style.position = 'relative';
        parent.appendChild(btn);
    });
});
</script>
