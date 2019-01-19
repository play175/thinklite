<?php import('public/header', ['title' => '新闻中心']);?>

<!-- Hero unit -->
<div class="page__header">
    <div class="hero__overlay hero__overlay--gradient"></div>
    <div class="hero__mask"></div>
    <div class="page__header__inner">
        <div class="container">
            <div class="page__header__content">
                <div class="page__header__content__inner" id='navConverter'>
                    <h1 class="page__header__title">新闻中心</h1>
                    <p class="page__header__text">This is mostly a simple layout, rather than a complete page unlike the others. However this is a really useful starting point for anything you want to create.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Page content -->
<div class="page">
    <div class="container">
        <div class="page__inner">
            <div class="page__menu">
                <ul class="vMenu">
                    <li><a href="#" class="vMenu--active">Active page</a></li>
                    <?php foreach($result['data'] as $k=>&$v){ ?>
                        <li>
                            <a href="<?php echo U('news/detail',['id'=>$v['id']])?>" ><?php echo $v["title"];?></a>
                        </li>   
                    <?php } ?>
                </ul>
            </div>
            <div class="page__main">
                <div class="text-container">
                    <h3 class="page__main__title">This is the main area</h3>
                    <p>Write or do whatever you want here!</p>
                </div>
            </div>
        </div>
    </div>
</div>


<?php import('public/footer');?>