<style>
.ai-helper-float {
    position: fixed;
    right: 24px;
    bottom: 24px;
    z-index: 9998;
    width: 58px;
    height: 58px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #173252, #c43d2f);
    color: #fff;
    box-shadow: 0 12px 30px rgba(23, 50, 82, .22);
    text-decoration: none;
    transition: transform .18s ease, box-shadow .18s ease;
}
.ai-helper-float:hover {
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 16px 36px rgba(23, 50, 82, .28);
}
.ai-helper-float i {
    font-size: 1.35rem;
}
.ai-helper-float span {
    position: absolute;
    right: 68px;
    white-space: nowrap;
    background: #173252;
    color: #fff;
    border-radius: 999px;
    padding: .45rem .7rem;
    font-size: .78rem;
    font-weight: 800;
    opacity: 0;
    pointer-events: none;
    transform: translateX(8px);
    transition: opacity .18s ease, transform .18s ease;
}
.ai-helper-float:hover span {
    opacity: 1;
    transform: translateX(0);
}
</style>
<a class="ai-helper-float" href="/integration/client/controllers/AIController.php?action=showAssistant" title="AI Helper">
    <span>AI Helper</span>
    <i class="bi bi-stars"></i>
</a>
