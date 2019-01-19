<?php import('public/header',['title'=>$detail['title']]);?>

<!-- Hero unit -->
<div class="page__header">
    <div class="hero__overlay hero__overlay--gradient"></div>
    <div class="hero__mask"></div>
    <div class="page__header__inner">
        <div class="container">
            <div class="page__header__content">
                <div class="page__header__content__inner" id='navConverter'>
                    <h1 class="page__header__title"><?php echo $detail['title'] ?></h1>
                    <p class="page__header__text">副标题在这里...</p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Page content -->
<div class="page">
    <div class="container">
        <div class="page__inner">
            <div class="page__main">
                <div class="text-container">
                    <h3 class="page__main__title"><?php echo $detail['title'] ?></h3>
                    <p><?php echo $detail['content']?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php import('public/footer');?>

