<?php

namespace dokuwiki\Ui;

use dokuwiki\ChangeLog\PageChangeLog;
use dokuwiki\ChangeLog\RevisionInfo;
use dokuwiki\Form\Form;

/**
 * DokuWiki PageRevisions Interface
 *
 * @package dokuwiki\Ui
 */
class PageRevisions extends Revisions
{
    /* @var PageChangeLog */
    protected $changelog;

    /**
     * PageRevisions Ui constructor
     *
     * @param string $id  id of page
     */
    public function __construct($id = null)
    {
        global $INFO;
        if (!isset($id)) $id = $INFO['id'];
        parent::__construct($id);
    }

    /** @inheritdoc */
    protected function setChangeLog()
    {
        $this->changelog = new PageChangeLog($this->id);
    }

    /**
     * Display list of old revisions of the page
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     * @author Ben Coburn <btcoburn@silicodon.net>
     * @author Kate Arzamastseva <pshns@ukr.net>
     * @author Satoshi Sahara <sahara.satoshi@gmail.com>
     *
     * @param int $first  skip the first n changelog lines
     * @return void
     */
    public function show($first = 0)
    {
        global $lang, $REV;
        $changelog =& $this->changelog;

        // get revisions, and set correct pagenation parameters (first, hasNext)
        if ($first === null) $first = 0;
        $hasNext = false;
        $revisions = $this->getRevisions($first, $hasNext);

        // print intro
        print p_locale_xhtml('revisions');

        // create the form
        $form = new Form([
                'id' => 'page__revisions',
                'class' => 'changes',
        ]);
        $form->addTagOpen('div')->addClass('no');

        // start listing
        $form->addTagOpen('ul');
        foreach ($revisions as $info) {
            $rev = $info['date'];
            $info['current'] = $changelog->isCurrentRevision($rev);

            $class = ($info['type'] === DOKU_CHANGE_TYPE_MINOR_EDIT) ? 'minor' : '';
            $form->addTagOpen('li')->addClass($class);
            $form->addTagOpen('div')->addClass('li');

            if (isset($info['current'])) {
                $form->addCheckbox('rev2[]')->val($rev);
            } elseif ($rev == $REV) {
                $form->addCheckbox('rev2[]')->val($rev)->attr('checked','checked');
            } elseif (page_exists($this->id, $rev)) {
                $form->addCheckbox('rev2[]')->val($rev);
            } else {
                $form->addCheckbox('')->val($rev)->attr('disabled','disabled');
            }
            $form->addHTML(' ');

            $RevInfo = new RevisionInfo($info);
            $html = implode(' ', [
                $RevInfo->editDate(true),      // edit date and time
                $RevInfo->difflinkRevision(),  // link to diffview icon
                $RevInfo->itemName(),          // name of page or media
                $RevInfo->editSummary(),       // edit summary
                $RevInfo->editor(),            // editor info
                $RevInfo->sizechange(),        // size change indicator
                $RevInfo->currentIndicator(),  // current indicator (only when k=1)
            ]);
            $form->addHTML($html);
            $form->addTagClose('div');
            $form->addTagClose('li');
        }
        $form->addTagClose('ul');  // end of revision list

        // show button for diff view
        $form->addButton('do[diff]', $lang['diff2'])->attr('type', 'submit');

        $form->addTagClose('div'); // close div class=no

        print $form->toHTML('Revisions');

        // provide navigation for paginated revision list (of pages and/or media files)
        print $this->navigation($first, $hasNext, function ($n) {
            return array('do' => 'revisions', 'first' => $n);
        });
    }
}
