jQuery(document).ready(function($) {
    function refreshCampaign(container) {
        const url = container.data('url');
        const refreshInterval = container.data('refresh');
        
        $.post(ajaxurl, {
            action: 'refresh_campaign',
            url: url
        }, function(response) {
            if (response.success) {
                const data = response.data;
                container.find('.amount-raised').text('$' + Number(data.amount_raised).toLocaleString());
                container.find('.goal').text('raised of $' + Number(data.goal).toLocaleString() + ' goal');
                container.find('.progress').css('width', data.percentage + '%');
                container.find('.supporters').text(data.supporters + ' supporters');
                container.find('.days-left').text(data.days_left + ' days left');
            }
        });
        
        // Schedule next refresh
        setTimeout(function() {
            refreshCampaign(container);
        }, refreshInterval * 1000);
    }
    
    // Initialize refresh for each campaign container
    $('.launchgood-campaign').each(function() {
        refreshCampaign($(this));
    });
});