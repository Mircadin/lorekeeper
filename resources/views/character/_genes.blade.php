@php
    $button = "";
    if (Auth::user()->hasPower('manage-characters') && Auth::user()->hasPower('view-genomes')) {
        $button .= "<a href=\"#\" class=\"btn btn-link btn-sm ";
        if($genome)
            $button .= "edit-genome\" data-genome-id=\"". $genome->id ."\"><i class=\"fas fa-cog\"";
        else
            $button .= "add-genome\"><i class=\"fas fa-plus\"";

        $button .= "></i></a>";
    }
@endphp

@if(!$genome)
    Unknown {!! $button !!}
@else
    @php
        $visible = 0;
        if(Auth::user()->hasPower('view-genomes')) $visible = 2;
        else if ($genome->visibility_level) $visible = $genome->visibility_level;
        else $visible = Settings::get('genome_default_visibility');
    @endphp
    @if($visible < 1)
        Hidden <a href="" class="btn btn-sm btn-link"><i class="fas fa-search"></i></a>
    @else
        @php
            $bool = $visible == 1;
            foreach ($genome->getLoci() as $loci) {
                $divOpen = '<div class="d-inline text-nowrap text-monospace mr-2" data-toggle="tooltip" title="'. $loci->name .'"">';
                echo($divOpen);
                if ($loci->type == "gene") {
                    if($bool)
                        foreach ($loci->alleles as $allele)
                            foreach ($genome->genes->where('loci_allele_id', $allele->id) as $item) {
                                echo($item->allele->displayName . "-");
                                break(2);
                            }
                    else
                        foreach ($genome->genes->where('loci_id', $loci->id) as $item)
                            echo($item->allele->displayName);
                } elseif ($loci->type == "gradient") {
                    $i = 0;
                    foreach ($genome->gradients->where('loci_id', $loci->id) as $item)
                        echo(($i++ == 0 ? "" : "</div>".$divOpen) . ($bool ? $item->displayValue : $item->displayGenome));
                } elseif ($loci->type == "numeric") {
                    $i = 0;
                    foreach ($genome->numerics->where('loci_id', $loci->id) as $item)
                        echo(($i++ == 0 ? "" : "</div>".$divOpen) . ($bool ? $item->estValue : $item->value));
                }
                echo('</div>');
            }
            if($genome->visibility_level != 2 && Auth::user()->hasPower('view-genomes'))
                echo add_help("This character's genome is either fully or partially hidden. You can only view its details because of your rank.");
                echo $button;
        @endphp
    @endif
@endif
