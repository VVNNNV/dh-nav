/**
 * 标签链接增强脚本
 * 将弹窗中的标签转换为可点击链接
 */
(function() {
    'use strict';

    // 等待 DOM 加载完成
    document.addEventListener('DOMContentLoaded', function() {
        // 监听弹窗打开事件或定期检查
        function enhanceTags() {
            // 查找弹窗中的标签容器
            var tagContainers = document.querySelectorAll('[class*="flex"][class*="flex-wrap"][class*="gap"]');
            
            tagContainers.forEach(function(container) {
                var badges = container.querySelectorAll('[class*="Badge"], span, div');
                
                badges.forEach(function(badge) {
                    // 检查是否已经处理过
                    if (badge.dataset.tagEnhanced) return;
                    
                    var text = badge.textContent.trim();
                    if (!text) return;
                    
                    // 排除非标签元素（如按钮文本）
                    var parent = badge.parentElement;
                    if (parent && (
                        parent.classList.contains('card-footer') ||
                        parent.tagName === 'BUTTON' ||
                        parent.tagName === 'A'
                    )) return;
                    
                    // 转换为 slug
                    var slug = text.toLowerCase().replace(/\s+/g, '-');
                    
                    // 创建链接
                    var link = document.createElement('a');
                    link.href = '/ai-tag/' + slug;
                    link.textContent = text;
                    link.className = badge.className + ' cursor-pointer hover:bg-primary hover:text-primary-foreground transition-colors no-underline';
                    link.dataset.tagEnhanced = 'true';
                    
                    // 阻止事件冒泡
                    link.addEventListener('click', function(e) {
                        e.stopPropagation();
                    });
                    
                    // 替换原元素
                    if (badge.parentNode) {
                        badge.parentNode.replaceChild(link, badge);
                    }
                });
            });
        }

        // 初始执行
        enhanceTags();

        // 使用 MutationObserver 监听弹窗打开
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    enhanceTags();
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
})();
