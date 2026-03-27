// Placeholder for user data, should be dynamically fetched from the server
const currentUser = "user1";

// Create a new blog post
document.getElementById("post-btn").addEventListener("click", function () {
    const postContent = document.getElementById("post-content").value;
    const postImage = document.getElementById("post-image").files[0];

    if (postContent.trim() === "") return;

    const post = document.createElement("div");
    post.className = "blog__post";
    post.dataset.author = currentUser;

    const postContentElem = document.createElement("p");
    postContentElem.className = "blog__post-content";
    postContentElem.innerText = postContent;
    post.appendChild(postContentElem);

    if (postImage) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const postImgElem = document.createElement("img");
            postImgElem.className = "blog__post-img";
            postImgElem.src = e.target.result;
            post.appendChild(postImgElem);
        };
        reader.readAsDataURL(postImage);
    }

    const deleteBtn = document.createElement("button");
    deleteBtn.className = "blog__delete-btn";
    deleteBtn.innerText = "Delete Post";
    deleteBtn.addEventListener("click", function () {
        if (confirm("Are you sure you want to delete this post?")) {
            post.remove();
        }
    });
    post.appendChild(deleteBtn);

    const commentSection = document.createElement("div");
    commentSection.className = "blog__comments";

    const commentInput = document.createElement("input");
    commentInput.className = "blog__comment-input";
    commentInput.placeholder = "Add a comment...";
    commentSection.appendChild(commentInput);

    const commentBtn = document.createElement("button");
    commentBtn.className = "blog__comment-btn";
    commentBtn.innerText = "Comment";
    commentSection.appendChild(commentBtn);

    const commentsList = document.createElement("div");
    commentSection.appendChild(commentsList);

    commentBtn.addEventListener("click", function () {
        const commentText = commentInput.value.trim();
        if (commentText === "") return;

        const comment = document.createElement("p");
        comment.className = "blog__comment";
        comment.innerText = `${currentUser}: ${commentText}`;
        commentsList.appendChild(comment);

        commentInput.value = "";
    });

    post.appendChild(commentSection);
    document.getElementById("posts").appendChild(post);

    document.getElementById("post-content").value = "";
    document.getElementById("post-image").value = null;
});
