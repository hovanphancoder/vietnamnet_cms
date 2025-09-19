document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.star');
    const ratingDisplay = document.getElementById('rating-display');
    const commentInput = document.getElementById('comment');
    const submitButton = document.getElementById('submit-comment');
    const commentsContainer = document.getElementById('comments-container');
    const container = document.querySelector('.wp-rating-container');
    const toggleButton = document.getElementById('toggle-comments');

    // Check required elements
    if (!container) {
        return;
    }

    // Handle show more/collapse button
    if (toggleButton) {
        let currentPage = 1;
        let isLoading = false;

        toggleButton.addEventListener('click', async function() {
            if (isLoading) return;
            
            try {
                isLoading = true;
                toggleButton.disabled = true;
                toggleButton.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Loading...
                `;

                currentPage++;
                const response = await fetch(`/api/reactix/get_comment/${posttype}/${postId}/paged/${currentPage}/`);
                const data = await response.json();

                if (data.status === 'success' && data.data.data.length > 0) {
                    // Add new comments to container
                    data.data.data.forEach(comment => {
                        const commentHTML = createCommentHTML(comment.content, comment.id);
                        commentsContainer.insertAdjacentHTML('beforeend', commentHTML);
                        
                        // Add replies if any
                        if (comment.children && comment.children.length > 0) {
                            const repliesContainer = document.getElementById(`replies-${comment.id}`);
                            comment.children.forEach(reply => {
                                const replyHTML = `
                                    <div class="flex items-start ml-12">
                                        <span class="relative flex shrink-0 overflow-hidden rounded-full h-8 w-8 mr-3">
                                            <img class="aspect-square h-full w-full"
                                                alt="${reply.user?.fullname || 'Anonymous User'}"
                                                src="${reply.user?.avatar || '/themes/cmsfullform/Frontend/assets/images/anonymous_60x60.png'}" />
                                        </span>
                                        <div class="flex-grow">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="font-medium text-sm">${reply.user?.fullname || 'Anonymous User'}</span>
                                                <span class="text-xs text-gray-500">${new Date(reply.created_at).toLocaleDateString('en-US')}</span>
                                            </div>
                                            <p class="text-sm text-gray-700">${reply.content}</p>
                                        </div>
                                    </div>
                                `;
                                repliesContainer.insertAdjacentHTML('beforeend', replyHTML);
                            });
                        }
                    });

                    // Check if no more comments to load
                    if (data.data.data.length < 5) {
                        toggleButton.style.display = 'none';
                    } else {
                        toggleButton.innerHTML = `
                            Show More
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-chevron-down ml-2 h-4 w-4">
                                <path d="m6 9 6 6 6-6"></path>
                            </svg>
                        `;
                    }
                } else {
                    // No more comments to load
                    toggleButton.style.display = 'none';
                }
            } catch (error) {
                showToast('Error occurred while loading more comments', 'error');
                toggleButton.innerHTML = `
                    Show More
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-chevron-down ml-2 h-4 w-4">
                        <path d="m6 9 6 6 6-6"></path>
                    </svg>
                `;
            } finally {
                isLoading = false;
                toggleButton.disabled = false;
            }
        });
    }

    let currentRating = 0;

    // Get posttype and post_id information from container
    const posttype = container.dataset.posttype;
    const postId = container.dataset.post_id;

    // Debug information

    // Function to create toast notification
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `fixed top-20 right-4 px-6 py-3 z-50 rounded-lg shadow-lg transform transition-all duration-500 translate-x-full ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white`;
        toast.textContent = message;
        document.body.appendChild(toast);

        // Show toast
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
        }, 100);

        // Hide and remove toast after 3 seconds
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 500);
        }, 3000);
    }

    // Function to create HTML for new comment
    function createCommentHTML(comment, commentId) {
        // Get user information from localStorage, if not available use default information
        let user = {
            fullname: 'Anonymous User',
            avatar: '/themes/cmsfullform/Frontend/assets/images/anonymous_60x60.png'
        };

        try {
            const storedUser = localStorage.getItem('user');
            if (storedUser) {
                const parsedUser = JSON.parse(storedUser);
                if (parsedUser && parsedUser.fullname) {
                    user = parsedUser;
                }
            }
        } catch (error) {
        }

        const date = new Date().toLocaleDateString('en-US');
        return `
            <div class="border-b border-gray-100 pb-6 last:border-0 last:pb-0" data-temp-id="${commentId}">
                <div class="flex items-start">
                    <span class="relative flex shrink-0 overflow-hidden rounded-full h-10 w-10 mr-4">
                        <img class="aspect-square h-full w-full"
                            alt="${user.fullname}"
                            src="${user.avatar}" />
                    </span>

                    <div class="flex-grow">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2"
                            data-comment-id="${commentId}">
                            <div>
                                <h4 class="font-medium text-gray-800">
                                    ${user.fullname}
                                </h4>

                                <div class="flex items-center mt-1">
                                    ${currentRating > 0 ? `
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4 text-yellow-400 fill-current"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                                            <path d="M12 17.3l6.18 3.73-1.64-7.03L21 9.24l-7.19-.61L12 2 10.19 8.63 3 9.24l5.46 4.76L6.82 21z"/>
                                        </svg>
                                        <span class="ml-1 text-sm text-gray-600">${currentRating}/5</span>
                                    ` : `
                                        <span class="ml-1 text-sm text-gray-600">Comment</span>
                                    `}
                                </div>
                            </div>

                            <span class="text-xs text-gray-500 mt-1 sm:mt-0">${date}</span>
                        </div>
                        <p class="text-gray-700 mb-3">${comment}</p>

                        <div class="flex items-center gap-4">
                            <button onclick="likeComment(${commentId})" class="like-btn inline-flex items-center gap-1 text-sm text-gray-500 hover:text-purple-600
                                        rounded-md px-3 h-9 hover:bg-accent transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M7 10v12"></path>
                                    <path d="M15 5.88 14 10h5.83A2 2 0 0 1 21.75 12.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76
                                            a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"/>
                                </svg>
                                <span>0</span>
                            </button>

                            <button onclick="toggleReplyInput(${commentId})" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-purple-600
                                        rounded-md px-3 h-9 hover:bg-accent transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 17H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <polyline points="16,3 21,3 21,8"></polyline>
                                    <line x1="8" y1="13" x2="16" y2="5"></line>
                                </svg>
                                <span>Reply</span>
                            </button>
                        </div>

                        <div id="replies-${commentId}" class="mt-4 space-y-4"></div>
                        <div id="reply-input-${commentId}" class="mt-4 hidden">
                            <div class="flex items-start gap-3">
                                <span class="relative flex shrink-0 overflow-hidden rounded-full h-8 w-8">
                                    <img class="aspect-square h-full w-full"
                                        alt="${user.fullname}"
                                        src="${user.avatar}" />
                                </span>
                                <div class="flex-grow">
                                    <textarea id="reply-text-${commentId}" 
                                        class="w-full p-3 border border-gray-200 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                        placeholder="Write your reply..."
                                        rows="2"></textarea>
                                    <div class="flex justify-end gap-2 mt-2">
                                        <button onclick="cancelReply(${commentId})" 
                                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition-colors">
                                            Cancel
                                        </button>
                                        <button onclick="submitReply(${commentId})" 
                                            class="px-4 py-2 text-sm bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                            Reply
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // Hàm cập nhật ID của comment
    function updateCommentId(tempId, newId) {
        const commentElement = document.querySelector(`[data-temp-id="${tempId}"]`);
        if (!commentElement) {
            return;
        }

        // Cập nhật data-temp-id thành data-comment-id
        commentElement.setAttribute('data-comment-id', newId);
        commentElement.removeAttribute('data-temp-id');

        // Cập nhật ID của các container con
        const replyInputContainer = commentElement.querySelector(`#reply-input-${tempId}`);
        if (replyInputContainer) {
            replyInputContainer.id = `reply-input-${newId}`;
        }

        const repliesContainer = commentElement.querySelector(`#replies-${tempId}`);
        if (repliesContainer) {
            repliesContainer.id = `replies-${newId}`;
        }

        // Cập nhật onclick handlers
        const likeButton = commentElement.querySelector('.like-btn');
        if (likeButton) {
            likeButton.setAttribute('onclick', `likeComment(${newId})`);
        }

        const replyButton = commentElement.querySelector('button[onclick^="toggleReplyInput"]');
        if (replyButton) {
            replyButton.setAttribute('onclick', `toggleReplyInput(${newId})`);
        }

        const replyInputField = commentElement.querySelector('input[onkeypress^="handleReplyKeyPress"]');
        if (replyInputField) {
            replyInputField.setAttribute('onkeypress', `handleReplyKeyPress(event, ${newId})`);
        }

        const sendReplyButton = commentElement.querySelector('button[onclick^="sendReply"]');
        if (sendReplyButton) {
            sendReplyButton.setAttribute('onclick', `sendReply(${newId})`);
        }
    }

    // Hàm xử lý like bình luận
    window.likeComment = async function(commentId) {
        try {
            // Kiểm tra xem comment đã được like chưa
            const likeButton = document.querySelector(`[data-comment-id="${commentId}"]`).closest('.flex-grow').querySelector('.like-btn');
            if (likeButton.classList.contains('text-purple-600')) {
                return;
            }

            const response = await fetch('/api/reactix/like_comment/' + commentId, {
                method: 'GET',
            });

            const data = await response.json();

            if (data.status === 'success') {
                // Tăng số lượng like
                const likeCount = likeButton.querySelector('span');
                const currentCount = parseInt(likeCount.textContent);
                likeCount.textContent = currentCount + 1;

                // Thêm class active và disabled
                likeButton.classList.add('text-purple-600');
                likeButton.classList.remove('text-gray-500');
                likeButton.classList.add('cursor-not-allowed');
                likeButton.classList.add('opacity-50');
                likeButton.disabled = true;

                // Thêm data attribute để đánh dấu đã like
                likeButton.setAttribute('data-liked', 'true');
            }
        } catch (error) {
            showToast('Có lỗi xảy ra khi like comment', 'error');
        }
    }

    // Hàm hiển thị/ẩn ô input trả lời
    window.toggleReplyInput = function(commentId) {
        const replyInput = document.getElementById(`reply-input-${commentId}`);
        replyInput.classList.toggle('hidden');
    }

    // Hàm xử lý khi nhấn Enter trong ô input trả lời
    window.handleReplyKeyPress = function(event, commentId) {
        if (event.key === 'Enter') {
            sendReply(commentId);
        }
    }

    // Hàm gửi trả lời
    window.sendReply = async function(commentId) {
        const replyInput = document.getElementById(`reply-input-${commentId}`);
        const replyText = replyInput.querySelector('input').value.trim();
        
        if (!replyText) return;

        // Lấy token từ cookie
        const cookies = document.cookie.split(';');
        const tokenCookie = cookies.find(cookie => cookie.trim().startsWith('cmsff_token='));
        const token = tokenCookie ? tokenCookie.split('=')[1].trim() : null;

        try {
            const formData = new FormData();
            formData.append('content', replyText);
            formData.append('posttype', posttype);
            formData.append('post_id', postId);
            formData.append('par_comment', commentId);

            const response = await fetch('/api/reactix/comment', {
                method: 'POST',
                headers: {
                    'Authorization': token ? `Bearer ${token}` : '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await response.json();

            if (data.status === 'success') {
                // Thêm reply vào container
                const repliesContainer = document.getElementById(`replies-${commentId}`);
                
                // Lấy thông tin user từ localStorage, nếu không có thì dùng thông tin mặc định
                let user = {
                    fullname: 'Anonymous User',
                    avatar: '/themes/cmsfullform/Frontend/assets/images/anonymous_60x60.png'
                };

                try {
                    const storedUser = localStorage.getItem('user');
                    if (storedUser) {
                        const parsedUser = JSON.parse(storedUser);
                        if (parsedUser && parsedUser.fullname) {
                            user = parsedUser;
                        }
                    }
                } catch (error) {
                }

                const date = new Date().toLocaleDateString('en-US');

                const replyHTML = `
                    <div class="flex items-start ml-12">
                        <span class="relative flex shrink-0 overflow-hidden rounded-full h-8 w-8 mr-3">
                            <img class="aspect-square h-full w-full"
                                alt="${user.fullname}"
                                src="${user.avatar}" />
                        </span>
                        <div class="flex-grow">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-medium text-sm">${user.fullname}</span>
                                <span class="text-xs text-gray-500">${date}</span>
                            </div>
                            <p class="text-sm text-gray-700">${replyText}</p>
                        </div>
                    </div>
                `;

                repliesContainer.insertAdjacentHTML('beforeend', replyHTML);

                // Reset input
                replyInput.querySelector('input').value = '';
                replyInput.classList.add('hidden');

                // Hiển thị thông báo thành công
                showToast('Gửi trả lời thành công!');
            } else {
                throw new Error(data.message || 'Có lỗi xảy ra');
            }
        } catch (error) {
            showToast(error.message || 'Có lỗi xảy ra khi gửi trả lời', 'error');
        }
    }

    // Xử lý submit form
    submitButton.addEventListener('click', async function(e) {
        e.preventDefault();
        
        if (!submitButton || !commentInput) {
            return;
        }

        const commentContent = commentInput.value.trim();
        if (!commentContent && currentRating === 0) {
            showToast('Vui lòng nhập nội dung bình luận hoặc đánh giá sao', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('rating', currentRating);
        formData.append('content', commentContent);
        formData.append('posttype', posttype);
        formData.append('post_id', postId);

        // Lấy token từ cookie
        const cookies = document.cookie.split(';');
        const tokenCookie = cookies.find(cookie => cookie.trim().startsWith('cmsff_token='));
        const token = tokenCookie ? tokenCookie.split('=')[1].trim() : null;

        try {
            submitButton.disabled = true;
            submitButton.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Đang gửi...
            `;

            // Tạo ID tạm thời
            const tempId = 'temp_' + Date.now();
            
            // Chỉ thêm comment vào container nếu có nội dung
            if (commentContent) {
                const newCommentHTML = createCommentHTML(commentContent, tempId);
                commentsContainer.insertAdjacentHTML('afterbegin', newCommentHTML);
            }

            const response = await fetch('/api/reactix/comment', {
                method: 'POST',
                headers: {
                    'Authorization': token ? `Bearer ${token}` : '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await response.json();

            if (data.status === 'success') {
                // Cập nhật ID tạm thành ID thật nếu có comment
                if (commentContent) {
                    updateCommentId(tempId, data.data);
                }

                // Reset form
                currentRating = 0;
                updateStars(0);
                updateRatingText(0);
                commentInput.value = '';
                updateSubmitButton();

                // Hiển thị thông báo thành công
                showToast('Gửi bình luận thành công!');
            } else {
                // Xóa comment tạm nếu có lỗi và có comment
                if (commentContent) {
                    const tempComment = document.querySelector(`[data-temp-id="${tempId}"]`);
                    if (tempComment) {
                        tempComment.remove();
                    }
                }
                throw new Error(data.message || 'Có lỗi xảy ra');
            }
        } catch (error) {
            showToast(error.message || 'Có lỗi xảy ra khi gửi bình luận', 'error');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = 'Gửi bình luận';
        }
    });

    // Hàm kiểm tra và cập nhật trạng thái nút gửi
    function updateSubmitButton() {
        if (!submitButton || !commentInput) return;
        
        const hasComment = commentInput.value.trim().length > 0;
        const shouldEnable = currentRating > 0 || hasComment;

        submitButton.disabled = !shouldEnable;
        
        if (shouldEnable) {
            submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            submitButton.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    // Hàm cập nhật hiển thị sao
    function updateStars(rating) {
        stars.forEach((star, index) => {
            if (index < rating) {
                star.src = star.dataset.srcStart;
            } else {
                star.src = '/themes/cmsfullform/Frontend/assets/images/star-empty.svg';
            }
        });
    }

    // Hàm cập nhật text đánh giá
    function updateRatingText(rating) {
        if (rating === 0) {
            ratingDisplay.textContent = 'Chưa đánh giá';
        } else {
            ratingDisplay.textContent = `${rating}/5`;
        }
    }

    // Xử lý sự kiện hover
    stars.forEach((star, index) => {
        star.addEventListener('mouseover', () => {
            updateStars(index + 1);
        });

        star.addEventListener('mouseout', () => {
            updateStars(currentRating);
        });

        star.addEventListener('click', () => {
            currentRating = index + 1;
            updateStars(currentRating);
            updateRatingText(currentRating);
            updateSubmitButton();
        });
    });

    // Xử lý sự kiện nhập comment
    commentInput.addEventListener('input', () => {
        updateSubmitButton();
    });

    // Khởi tạo trạng thái ban đầu
    updateSubmitButton();
}); 