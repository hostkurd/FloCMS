<?php

class Model{
    
    protected $db;

    public function __construct(){
        $this->db = App::$db;
    }

    public function pagingArray($pageId, $limit, $total){
        $total_pages = ceil($total/$limit);

        $hasNextPage = true;
        $hasPrevPage = true;

        $nextPage = $pageId + 1;
        $prevPage = $pageId - 1;

        if($pageId >= $total_pages){
            $hasNextPage = false;
            $nextPage = 0;
        }

        if ($pageId <= 1){
            $hasPrevPage = false;
            $previousPage =0;
        }

        $lastPages = array();

        if($total_pages > 6){
            $lastPages += $total_pages-1;
            $lastPages += $total_pages-2;
            $lastPages += $total_pages-3;
        }
        $result = array(
            'items_count'=>$total,
            'total_pages'=>$total_pages,
            'cur_page'=>$pageId,
            'per_page'=>$limit, 
            'next_page'=>$nextPage, 
            'prev_page'=>$prevPage, 
            'has_next'=>$hasNextPage, 
            'has_prev'=>$hasPrevPage,
            'last_page'=>$total_pages,
            'last_pages'=>$lastPages
        );

        return $result;
    }


    private function GenPagePath($pageId, $curId, $PageLink){
        $result = '';

        $result .= '<li class="page-item '.($pageId == $curId ? 'active': '').'">';
        $result .= $pageId!=$curId?'<a class="page-link" href="'.$PageLink.$curId.'">'.$curId.'</a>': '<span class="page-link">'.$curId.'</span>';

        $result .= '</li>';

        return $result;
    }

    public function paginationData($pageid, $limit, $total, $controller,$subControl = null, $role='admin', $paramS = 1){ 
        $next_str = 'Next';
        $prev_str = 'Prev';
        $plusD = '';
        $minD = '';
        $path =SITE_URI.DS.ACTIVE_LANG_PATH.'admin/'.$controller.'/list/';
        if($subControl != null){$path =SITE_URI.DS.ACTIVE_LANG_PATH.'admin/'.$controller.'/'.$subControl.'/';}
        
    
        if($role == 'user'){
            $path =SITE_URI.DS.ACTIVE_LANG.DS.$controller.'/page/';
        }
    
        $page2Link='';
        $pageLast='';
        $pageBeforeLast='';
        $ellipsis = '';
        $pageid = (int)$pageid;
        if ($paramS !=1){
            $total = 1;
        }
    
        $pageidPlus = $pageid + 1;
        $pageidMin = $pageid - 1;
    
        $nextLink = '<a href="'.$path.$pageidPlus .'" class="page-link" aria-label="Next page">'.$next_str.'</a>';
        $prevLink = '<a href="'.$path.$pageidMin .'" class="page-link" aria-label="Previous page">'.$prev_str.'</a>';
        $total_pages = ceil($total/$limit);
        $beforelastid =$total_pages-1;
    
        if($pageid >= $total_pages){
            $plusD = 'disabled';
            $nextLink = '<span class="page-link">'.$next_str.'</span>';
        }
        if ($pageid <= 1){
            $minD = 'disabled';
            $prevLink ='<span class="page-link">'.$prev_str.'</span>';
        }
    
        $totalViewed = $limit * $pageid;
    
        if($totalViewed > $total){
            $totalViewed = $total;
        }
    
        //Head
        $html = "<div class=\"card-footer px-3 border-0 d-flex flex-column flex-lg-row align-items-center justify-content-between\">";
        $html .= "<nav aria-label=\"Page navigation\">";
        $html .= "<ul class=\"pagination mb-0\">";
    
        // Content
        $html .= "<li class=\"page-item\">".$prevLink."</li>";
    
        for($i=1; $i<=$total_pages; $i++){
            
            $html .= Self::GenPagePath($pageid,$i,$path); 
    
        }
        
        $html .= "<li class=\"page-item\">".$nextLink."</li>";
    
        // Foot
        $html .= "</ul>";
        $html .= "</nav>";
        $html .= "<div class=\"fw-normal small mt-4 mt-lg-0\">Showing <b>".$totalViewed."</b> out of <b>".$total."</b> entries</div>";
        $html .= "</div>";
    
        Return $html;
    }
}

// <nav class="d-flex" aria-label="pagination">
//   <ul class="pagination">
//     <li class="page-item disabled">
//       <a class="page-link" href="#" aria-label="Previous">
//         <span aria-hidden="true"><i class="uil uil-arrow-left"></i></span>
//       </a>
//     </li>
//     <li class="page-item active"><a class="page-link" href="#">1</a></li>
//     <li class="page-item"><a class="page-link" href="#">2</a></li>
//     <li class="page-item"><a class="page-link" href="#">3</a></li>
//     <li class="page-item">
//       <a class="page-link" href="#" aria-label="Next">
//         <span aria-hidden="true"><i class="uil uil-arrow-right"></i></span>
//       </a>
//     </li>
//   </ul>
// </nav>
