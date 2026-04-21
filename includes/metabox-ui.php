<style>
.ai-tool-meta-table { width: 100%; }
.ai-tool-meta-table td { padding: 8px 0; vertical-align: top; }
.ai-tool-meta-table td:first-child { width: 90px; font-weight: bold; }
.ai-tool-meta-table input[type="text"], .ai-tool-meta-table input[type="url"], .ai-tool-meta-table input[type="number"], .ai-tool-meta-table textarea { width: 100%; max-width: 450px; }
.ai-tool-meta-table input[type="checkbox"] { width: auto; margin-right: 6px; }
.ai-tool-meta-table .description { color: #666; font-size: 12px; margin-left: 8px; }
.ai-fetch-btn { background: #2271b1; color: #fff; border: none; padding: 8px 16px; cursor: pointer; border-radius: 3px; margin-left: 10px; }
.ai-fetch-btn:hover { background: #135e96; }
.ai-fetch-btn:disabled { background: #ccc; cursor: not-allowed; }
.ai-tag-item { display: inline-block; background: #e2e4e7; padding: 3px 10px; margin: 2px; border-radius: 3px; font-size: 12px; cursor: pointer; }
.ai-tag-item:hover { background: #c3c9d0; }
/* 图标选择器 */
.ai-icon-picker-btn { background: #f0f0f1; border: 1px solid #8c8f94; padding: 4px 12px; cursor: pointer; border-radius: 3px; font-size: 13px; margin-left: 8px; vertical-align: middle; }
.ai-icon-picker-btn:hover { background: #e5e5e6; border-color: #2271b1; color: #2271b1; }
.ai-icon-picker { display: none; margin-top: 6px; border: 1px solid #ccd0d4; border-radius: 4px; background: #fff; max-width: 420px; overflow: hidden; }
.ai-icon-picker.open { display: flex; flex-direction: column; }
.ai-icon-picker-header { display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; border-bottom: 1px solid #eee; font-size: 13px; font-weight: 500; background: #f8f8f8; }
.ai-icon-picker-close { background: none; border: none; font-size: 14px; cursor: pointer; color: #666; padding: 2px 6px; border-radius: 3px; }
.ai-icon-picker-close:hover { background: #e2e2e2; }
.ai-icon-picker-grid { display: grid; grid-template-columns: repeat(10, 1fr); gap: 1px; padding: 8px; overflow-y: auto; max-height: 220px; }
.ai-icon-picker-item { width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; font-size: 18px; border: none; background: transparent; border-radius: 4px; cursor: pointer; transition: all 0.1s; }
.ai-icon-picker-item:hover { background: #e0f2fe; transform: scale(1.15); }
.ai-icon-picker-item.selected { background: #dbeafe; box-shadow: 0 0 0 2px #2271b1; }
</style>
<?php
global $post;
$tool_url = get_post_meta($post->ID, 'tool_url', true);
$tool_icon = get_post_meta($post->ID, 'tool_icon', true);
$tool_hot = get_post_meta($post->ID, 'tool_hot', true);
$tool_order = get_post_meta($post->ID, 'tool_order', true);
$current_tags = $post->ID ? wp_get_post_terms($post->ID, 'ai_tag', array('fields' => 'names')) : array();

$default_category_id = '';
if ($post->ID) {
    $current_categories = wp_get_post_terms($post->ID, 'ai_category', array('fields' => 'ids'));
    if (empty($current_categories)) {
        $default_cat = get_term_by('name', 'AI网站', 'ai_category');
        $default_category_id = $default_cat ? $default_cat->term_id : '';
    } else {
        $default_category_id = $current_categories[0];
    }
}

wp_nonce_field('ai_navigator_save_meta', 'ai_navigator_meta_nonce');
?>
<input type="hidden" name="ai_default_category" value="<?php echo esc_attr($default_category_id); ?>">
<table class="ai-tool-meta-table">
<tr><td>🌐 网址</td><td>
<input type="url" id="tool_url" name="tool_url" value="<?php echo esc_attr($tool_url); ?>" placeholder="https://..." style="width:350px;">
<button type="button" id="ai_fetch_btn" class="ai-fetch-btn">🚀 自动获取</button>
<span id="ai_fetch_status"></span>
</td></tr>
<tr><td>📝 标题</td><td><input type="text" id="title" name="post_title" value="<?php echo esc_attr($post->post_title); ?>" style="width:350px;"></td></tr>
<tr><td>📄 简介</td><td><textarea id="excerpt" name="excerpt" rows="2" style="width:100%;max-width:450px;"><?php echo esc_textarea($post->post_excerpt); ?></textarea></td></tr>
<tr><td>🎯 图标</td><td>
<input type="text" id="tool_icon" name="tool_icon" value="<?php echo esc_attr($tool_icon); ?>" placeholder="🤖" style="width:60px;" maxlength="10">
<button type="button" class="ai-icon-picker-btn" onclick="toggleAdminIconPicker()">📋 选择图标</button>
<span class="description">点击选择或直接输入 Emoji</span>
<div class="ai-icon-picker" id="aiIconPicker">
    <div class="ai-icon-picker-header">
        <span>选择图标</span>
        <button type="button" class="ai-icon-picker-close" onclick="toggleAdminIconPicker()">✕</button>
    </div>
    <div class="ai-icon-picker-grid">
        <?php foreach (ai_navigator_get_icons() as $icon) : ?>
        <button type="button" class="ai-icon-picker-item" onclick="selectAdminIcon('<?php echo esc_js($icon); ?>')" title="<?php echo esc_attr($icon); ?>"><?php echo $icon; ?></button>
        <?php endforeach; ?>
    </div>
</div>
</td></tr>
<tr><td>🏷️ 标签</td><td><input type="text" id="ai_tags_input" name="ai_tags_input" value="<?php echo esc_attr(implode(', ', $current_tags)); ?>" placeholder="标签1, 标签2" style="width:350px;">
<div id="ai_tag_suggestions" style="margin-top:5px;"></div>
</td></tr>
<tr><td>🔥 热门</td><td><label><input type="checkbox" id="tool_hot" name="tool_hot" value="1" <?php checked($tool_hot, '1'); ?>> 设为热门</label></td></tr>
<tr><td>📊 排序</td><td><input type="number" id="tool_order" name="tool_order" value="<?php echo esc_attr($tool_order ?: 0); ?>" min="0" style="width:80px;"> <span class="description">数字越小越靠前</span></td></tr>
</table>
<script>
jQuery(function($) {
    var suggestedTags = [];
    $("#ai_fetch_btn").click(function() {
        var url = $("#tool_url").val().trim();
        if (!url) { alert("请先输入网址"); return; }
        var btn = $(this), status = $("#ai_fetch_status");
        btn.prop("disabled", true).text("获取中...");
        status.html('<span style="color:#666">获取中...</span>');
        $.get("/wp-json/ai-navigator/v1/fetch-url?url=" + encodeURIComponent(url), function(data) {
            if (data.title) $("#title").val(data.title);
            if (data.description) $("#excerpt").val(data.description);
            if (data.icon) { $("#tool_icon").val(data.icon); updateAdminIconSelected(); }
            if (data.tags && data.tags.length) {
                suggestedTags = data.tags;
                $("#ai_tags_input").val(suggestedTags.join(", "));
                var html = "建议: ";
                suggestedTags.forEach(function(t){ html += '<span class="ai-tag-item">'+t+'</span> '; });
                $("#ai_tag_suggestions").html(html);
            }
            status.html('<span style="color:green">✓ 成功</span>');
            btn.prop("disabled", false).text("🚀 自动获取");
            setTimeout(function(){ status.html(""); }, 3000);
        }).fail(function() {
            status.html('<span style="color:red">✗ 失败</span>');
            btn.prop("disabled", false).text("🚀 自动获取");
        });
    });
    $("#ai_tag_suggestions").on("click", ".ai-tag-item", function() {
        var tag = $(this).text(), current = $("#ai_tags_input").val(), tags = current ? current.split(",").map(function(t){return t.trim()}) : [];
        if (tags.indexOf(tag) === -1) { tags.push(tag); $("#ai_tags_input").val(tags.join(", ")); }
    });

    // 图标选择器
    window.toggleAdminIconPicker = function() {
        var picker = document.getElementById('aiIconPicker');
        picker.classList.toggle('open');
        if (picker.classList.contains('open')) updateAdminIconSelected();
    };
    window.selectAdminIcon = function(icon) {
        document.getElementById('tool_icon').value = icon;
        updateAdminIconSelected();
    };
    function updateAdminIconSelected() {
        var val = document.getElementById('tool_icon').value.trim();
        document.querySelectorAll('.ai-icon-picker-item').forEach(function(el) {
            el.classList.toggle('selected', el.textContent.trim() === val);
        });
    }
    // 监听手动输入
    document.getElementById('tool_icon').addEventListener('input', updateAdminIconSelected);
});
</script>
